<?php
namespace Petshop\SampleData\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;

class SeedPetCatalog implements DataPatchInterface
{
    private const ATTRIBUTE_SET_ID = 4;

    public function __construct(
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductInterfaceFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SourceItemInterfaceFactory $sourceItemFactory,
        private readonly SourceItemsSaveInterface $sourceItemsSave
    ) {
    }

    public function apply()
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function () {
            $this->seedCatalog();
        });
    }

    private function seedCatalog(): void
    {
        $rootId = (int) $this->storeManager->getStore()->getRootCategoryId();

        $shopId = $this->getOrCreateCategory($rootId, 'Shop', 'shop');
        $newArrivalsId = $this->getOrCreateCategory($rootId, "What's New", 'new-arrivals');
        $categoriesId = $this->getOrCreateCategory($rootId, 'Categories', 'categories');
        $brandsId = $this->getOrCreateCategory($rootId, 'Brands', 'brands');
        $promosId = $this->getOrCreateCategory($rootId, 'Promos', 'promos');

        $catsId = $this->getOrCreateCategory($categoriesId, 'Cats', 'cats');
        $dogsId = $this->getOrCreateCategory($categoriesId, 'Dogs', 'dogs');
        $birdsId = $this->getOrCreateCategory($categoriesId, 'Birds', 'birds');
        $treatsId = $this->getOrCreateCategory($categoriesId, 'Treats', 'treats');
        $dryFoodId = $this->getOrCreateCategory($categoriesId, 'Dry Food', 'dry-food');
        $containersId = $this->getOrCreateCategory($categoriesId, 'Food Containers', 'food-containers');

        $this->getOrCreateCategory($brandsId, 'PawPure', 'pawpure');
        $this->getOrCreateCategory($brandsId, 'CanineChoice', 'caninechoice');
        $this->getOrCreateCategory($brandsId, 'FeatherFresh', 'featherfresh');

        $clearanceId = $this->getOrCreateCategory($promosId, 'Clearance', 'clearance');
        $seasonalId = $this->getOrCreateCategory($promosId, 'Seasonal Deals', 'seasonal-deals');
        $limitedId = $this->getOrCreateCategory($promosId, 'Limited Time Offers', 'limited-time-offers');

        $petTypes = [
            ['name' => 'Cat', 'category_id' => $catsId],
            ['name' => 'Dog', 'category_id' => $dogsId],
            ['name' => 'Bird', 'category_id' => $birdsId]
        ];

        $lines = [
            ['title' => 'Dry Food', 'base_price' => 19.99, 'category_id' => $dryFoodId],
            ['title' => 'Premium Treats', 'base_price' => 8.99, 'category_id' => $treatsId],
            ['title' => 'Airtight Food Container', 'base_price' => 24.50, 'category_id' => $containersId],
            ['title' => 'Grain-Free Formula', 'base_price' => 27.90, 'category_id' => $dryFoodId],
            ['title' => 'Training Bites', 'base_price' => 10.25, 'category_id' => $treatsId],
            ['title' => 'Stackable Storage Bin', 'base_price' => 29.99, 'category_id' => $containersId],
            ['title' => 'Digestive Support Kibble', 'base_price' => 22.75, 'category_id' => $dryFoodId],
            ['title' => 'Soft Chew Rewards', 'base_price' => 9.45, 'category_id' => $treatsId],
            ['title' => 'Travel Food Jar', 'base_price' => 16.35, 'category_id' => $containersId],
            ['title' => 'High-Protein Blend', 'base_price' => 31.20, 'category_id' => $dryFoodId]
        ];

        $promoCategories = [$clearanceId, $seasonalId, $limitedId];

        $count = 0;
        foreach ($petTypes as $petType) {
            foreach ($lines as $index => $line) {
                $count++;
                $sku = sprintf('petshop-%s-%02d', strtolower($petType['name']), $count);
                $name = sprintf('%s %s', $petType['name'], $line['title']);
                $price = $line['base_price'] + ($count % 7);
                $isNew = $count <= 18;
                $isPromo = ($count % 4) === 0;

                $categoryIds = [
                    $shopId,
                    $petType['category_id'],
                    $line['category_id']
                ];

                if ($isNew) {
                    $categoryIds[] = $newArrivalsId;
                }

                if ($isPromo) {
                    $categoryIds[] = $promoCategories[$count % count($promoCategories)];
                }

                $description = sprintf(
                    '%s %s designed for daily nutrition and freshness. Balanced ingredients, pet-safe packaging, and quality-tested by Petshop.',
                    $petType['name'],
                    $line['title']
                );

                $this->upsertProduct(
                    sku: $sku,
                    name: $name,
                    price: $price,
                    categoryIds: array_values(array_unique($categoryIds)),
                    description: $description,
                    isNew: $isNew,
                    isPromo: $isPromo
                );
            }
        }
    }

    private function upsertProduct(
        string $sku,
        string $name,
        float $price,
        array $categoryIds,
        string $description,
        bool $isNew,
        bool $isPromo
    ): void {
        try {
            $product = $this->productRepository->get($sku, false, null, true);
        } catch (NoSuchEntityException) {
            $product = $this->productFactory->create();
            $product->setSku($sku);
            $product->setTypeId(Type::TYPE_SIMPLE);
            $product->setAttributeSetId(self::ATTRIBUTE_SET_ID);
            $product->setWebsiteIds([1]);
        }

        /** @var Product $product */
        $product->setName($name);
        $product->setStatus(Status::STATUS_ENABLED);
        $product->setVisibility(Visibility::VISIBILITY_BOTH);
        $product->setPrice($price);
        $product->setTaxClassId(2);
        $product->setCategoryIds($categoryIds);
        $product->setCustomAttribute('description', $description);
        $product->setCustomAttribute('short_description', $description);

        if ($isNew) {
            $product->setCustomAttribute('news_from_date', date('Y-m-d'));
            $product->setCustomAttribute('news_to_date', date('Y-m-d', strtotime('+60 days')));
        }

        if ($isPromo) {
            $product->setSpecialPrice(round($price * 0.85, 2));
            $product->setSpecialFromDate(date('Y-m-d'));
            $product->setSpecialToDate(date('Y-m-d', strtotime('+45 days')));
        }

        $this->productRepository->save($product);

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setSku($sku);
        $sourceItem->setQuantity((float) rand(45, 250));
        $sourceItem->setStatus(1);
        $this->sourceItemsSave->execute([$sourceItem]);
    }

    private function getOrCreateCategory(
        int $parentId,
        string $name,
        string $urlKey
    ): int {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['entity_id', 'url_key'])
            ->addAttributeToFilter('parent_id', $parentId)
            ->addAttributeToFilter('url_key', $urlKey)
            ->setPageSize(1);

        $existing = $collection->getFirstItem();
        if ($existing && $existing->getId()) {
            return (int) $existing->getId();
        }

        $category = $this->categoryFactory->create();
        $category->setParentId($parentId);
        $category->setName($name);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setUrlKey($urlKey);
        $category->setPath((string) $parentId);
        $saved = $this->categoryRepository->save($category);

        return (int) $saved->getId();
    }

    public static function getDependencies(): array
    {
        return [CreateSampleProducts::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
