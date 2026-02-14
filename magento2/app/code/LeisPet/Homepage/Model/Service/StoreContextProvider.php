<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\Service;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Category;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as CmsPageCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gathers real store data (categories, routes, store info) so that AI prompts
 * and fallback content always reference actual, existing URLs and categories.
 *
 * This prevents the AI from inventing non-existent routes like "/dog-food-treats".
 */
class StoreContextProvider
{
    /** @var array<string, mixed>|null */
    private ?array $cachedContext = null;

    public function __construct(
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly CmsPageCollectionFactory $cmsPageCollectionFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Returns a structured snapshot of real store data.
     *
     * @return array{
     *     store_name: string,
     *     base_url: string,
     *     categories: list<array{id: int, name: string, url_path: string, product_count: int, level: int}>,
     *     categories_with_products: list<array{id: int, name: string, url_path: string, product_count: int}>,
     *     available_routes: list<string>
     * }
     */
    public function getContext(): array
    {
        if ($this->cachedContext !== null) {
            return $this->cachedContext;
        }

        try {
            $this->cachedContext = $this->buildContext();
        } catch (\Throwable $e) {
            $this->logger->warning('StoreContextProvider: failed to build context', [
                'exception' => $e->getMessage()
            ]);
            $this->cachedContext = $this->emptyContext();
        }

        return $this->cachedContext;
    }

    /**
     * Returns a human-readable text block summarising available store data.
     * Suitable for injecting directly into an AI prompt.
     */
    public function getContextForPrompt(): string
    {
        $ctx = $this->getContext();
        $lines = [];

        $lines[] = sprintf('Store name: %s', $ctx['store_name']);
        $lines[] = sprintf('Storefront base URL: %s', $ctx['base_url']);
        $lines[] = '';

        // Categories with products (the ones that matter for links)
        if (!empty($ctx['categories_with_products'])) {
            $lines[] = 'REAL categories that have products (use ONLY these for URLs):';
            foreach ($ctx['categories_with_products'] as $cat) {
                $lines[] = sprintf(
                    '  - "%s" → url_path: "/%s" (%d products)',
                    $cat['name'],
                    $cat['url_path'],
                    $cat['product_count']
                );
            }
            $lines[] = '';
        }

        // All categories (including empty ones)
        if (!empty($ctx['categories'])) {
            $lines[] = 'All categories (including empty):';
            foreach ($ctx['categories'] as $cat) {
                $indent = str_repeat('  ', max(0, $cat['level'] - 2));
                $lines[] = sprintf(
                    '  %s- "%s" → "/%s" (%d products)',
                    $indent,
                    $cat['name'],
                    $cat['url_path'],
                    $cat['product_count']
                );
            }
            $lines[] = '';
        }

        // Known static routes
        $lines[] = 'Known storefront routes:';
        foreach ($ctx['available_routes'] as $route) {
            $lines[] = '  - ' . $route;
        }

        return implode("\n", $lines);
    }

    /**
     * Returns only categories that have at least 1 product.
     *
     * @return list<array{id: int, name: string, url_path: string, product_count: int}>
     */
    public function getCategoriesWithProducts(): array
    {
        return $this->getContext()['categories_with_products'];
    }

    // ── Internal ─────────────────────────────────────────────────────────

    private function buildContext(): array
    {
        $store = $this->storeManager->getStore();
        $baseUrl = rtrim((string)$store->getBaseUrl(), '/');
        $storeName = (string)$store->getName();

        // Fetch all active categories
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'url_path', 'is_active', 'level'])
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('level', ['gt' => 1])  // skip root categories
            ->setOrder('level', 'ASC')
            ->setOrder('position', 'ASC');

        // Join catalog_category_product to get actual product counts
        $collection->getSelect()->joinLeft(
            ['cp' => $collection->getTable('catalog_category_product')],
            'cp.category_id = e.entity_id',
            ['product_count' => new Expression('COUNT(cp.product_id)')]
        )->group('e.entity_id');

        // Read the category URL suffix from store config (e.g. ".html")
        $categorySuffix = (string)$this->scopeConfig->getValue(
            'catalog/seo/category_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $allCategories = [];
        $withProducts = [];

        /** @var Category $category */
        foreach ($collection as $category) {
            $urlPath = (string)$category->getData('url_path');
            $productCount = (int)$category->getData('product_count');
            $name = (string)$category->getName();
            $level = (int)$category->getData('level');

            if ($urlPath === '' || $name === '') {
                continue;
            }

            // Append the SEO suffix so URLs match the real storefront (e.g. /categories/dogs.html)
            $urlPath .= $categorySuffix;

            $entry = [
                'id'            => (int)$category->getId(),
                'name'          => $name,
                'url_path'      => $urlPath,
                'product_count' => $productCount,
                'level'         => $level,
            ];

            $allCategories[] = $entry;

            if ($productCount > 0) {
                $withProducts[] = [
                    'id'            => $entry['id'],
                    'name'          => $entry['name'],
                    'url_path'      => $entry['url_path'],
                    'product_count' => $entry['product_count'],
                ];
            }
        }

        // Build routes dynamically — no hardcoded paths
        $availableRoutes = [
            '/',              // homepage
            '/contact',       // standard Magento contact page
            '/search.html',   // standard Magento search
        ];

        // Add ALL category url_paths (even empty ones are valid routes)
        foreach ($allCategories as $cat) {
            $route = '/' . ltrim($cat['url_path'], '/');
            if (!in_array($route, $availableRoutes, true)) {
                $availableRoutes[] = $route;
            }
        }

        // Add CMS page routes
        try {
            $cmsPages = $this->cmsPageCollectionFactory->create();
            $cmsPages->addFieldToFilter('is_active', 1);
            foreach ($cmsPages as $page) {
                $identifier = (string)$page->getData('identifier');
                if ($identifier !== '' && $identifier !== 'home' && $identifier !== 'no-route') {
                    $route = '/' . ltrim($identifier, '/');
                    if (!in_array($route, $availableRoutes, true)) {
                        $availableRoutes[] = $route;
                    }
                }
            }
        } catch (\Throwable $e) {
            // CMS pages are optional context — don't break if unavailable
            $this->logger->debug('StoreContextProvider: could not load CMS pages', [
                'exception' => $e->getMessage()
            ]);
        }

        sort($availableRoutes);

        return [
            'store_name'               => $storeName,
            'base_url'                 => $baseUrl,
            'categories'               => $allCategories,
            'categories_with_products' => $withProducts,
            'available_routes'         => $availableRoutes,
        ];
    }

    /**
     * @return array{store_name: string, base_url: string, categories: list<never>, categories_with_products: list<never>, available_routes: list<string>}
     */
    private function emptyContext(): array
    {
        return [
            'store_name'               => 'LeisPet',
            'base_url'                 => '',
            'categories'               => [],
            'categories_with_products' => [],
            'available_routes'         => ['/', '/shop'],
        ];
    }
}
