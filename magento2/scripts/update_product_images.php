<?php

declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\Processor as GalleryProcessor;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

require __DIR__ . '/../app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

/** @var State $state */
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (LocalizedException $e) {
    // Area code already set.
}

/** @var CollectionFactory $collectionFactory */
$collectionFactory = $objectManager->get(CollectionFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var GalleryProcessor $galleryProcessor */
$galleryProcessor = $objectManager->get(GalleryProcessor::class);

$dryRun = in_array('--dry-run', $argv, true);
$limit = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int) substr($arg, 8);
    }
}

/**
 * Public stock images (Wikimedia Commons).
 * Script validates URL availability before assignment.
 */
const IMAGE_POOL = [
    'bird' => [
        'https://upload.wikimedia.org/wikipedia/commons/3/32/House_sparrow04.jpg',
        'https://upload.wikimedia.org/wikipedia/commons/9/9a/Gull_portrait_ca_usa.jpg',
        'https://upload.wikimedia.org/wikipedia/commons/5/5b/Parrot_green.jpg'
    ],
    'cat' => [
        'https://upload.wikimedia.org/wikipedia/commons/b/b6/Felis_catus-cat_on_snow.jpg',
        'https://upload.wikimedia.org/wikipedia/commons/a/a3/June_odd-eyed-cat.jpg',
        'https://upload.wikimedia.org/wikipedia/commons/3/3a/Cat03.jpg'
    ],
    'dog' => [
        'https://upload.wikimedia.org/wikipedia/commons/6/6e/Golde33443.jpg',
        'https://upload.wikimedia.org/wikipedia/commons/5/5f/Alaskan_Malamute.jpg',
        'https://upload.wikimedia.org/wikipedia/commons/6/6b/Taka_Shiba.jpg'
    ]
];

const GALLERY_IMAGES_PER_PRODUCT = 3;

function isUrlAvailable(string $url): bool
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_USERAGENT => 'PetshopImageSeeder/1.0'
    ]);

    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 400;
}

function downloadImage(string $url, string $targetPath): bool
{
    $ch = curl_init($url);
    $fp = fopen($targetPath, 'wb');

    if (!$fp) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'PetshopImageSeeder/1.0'
    ]);

    $ok = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
    fclose($fp);

    return $ok !== false && $code >= 200 && $code < 400 && filesize($targetPath) > 1024;
}

function detectPoolKey(string $sku, string $name): ?string
{
    $haystack = strtolower($sku . ' ' . $name);

    if (strpos($haystack, 'bird') !== false) {
        return 'bird';
    }

    if (strpos($haystack, 'cat') !== false) {
        return 'cat';
    }

    if (strpos($haystack, 'dog') !== false) {
        return 'dog';
    }

    return null;
}

$availablePool = [];
foreach (IMAGE_POOL as $pool => $urls) {
    $availablePool[$pool] = array_values(array_filter($urls, static fn($url) => isUrlAvailable($url)));
    if (!$availablePool[$pool]) {
        echo "[WARN] No reachable URLs found for pool: {$pool}\n";
    }
}

$tmpDir = BP . '/pub/media/import/petshop-images';
if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
    throw new RuntimeException('Could not create temp directory: ' . $tmpDir);
}

$collection = $collectionFactory->create();
$collection->addAttributeToSelect(['name', 'sku'])
    ->addAttributeToFilter('sku', ['like' => 'petshop-%'])
    ->setOrder('entity_id', 'ASC');

if ($limit !== null && $limit > 0) {
    $collection->setPageSize($limit)->setCurPage(1);
}

$poolCursor = ['bird' => 0, 'cat' => 0, 'dog' => 0];
$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($collection as $item) {
    $sku = (string) $item->getSku();
    $name = (string) $item->getName();
    $poolKey = detectPoolKey($sku, $name);

    if ($poolKey === null || empty($availablePool[$poolKey])) {
        echo "[SKIP] {$sku}: no matching available image pool\n";
        $skipped++;
        continue;
    }

    $urls = $availablePool[$poolKey];
    $safeSku = preg_replace('/[^a-z0-9\-_]+/i', '-', $sku);
    $imagesForProduct = min(GALLERY_IMAGES_PER_PRODUCT, count($urls));

    $selectedUrls = [];
    for ($i = 0; $i < $imagesForProduct; $i++) {
        $selectedUrls[] = $urls[($poolCursor[$poolKey] + $i) % count($urls)];
    }
    $poolCursor[$poolKey]++;

    $downloadedFiles = [];
    foreach ($selectedUrls as $index => $url) {
        $targetFile = $tmpDir . '/' . $safeSku . '-' . ($index + 1) . '.jpg';
        if (!downloadImage($url, $targetFile)) {
            echo "[FAIL] {$sku}: could not download {$url}\n";
            $failed++;
            continue 2;
        }

        $downloadedFiles[] = $targetFile;
    }

    if ($dryRun) {
        echo "[DRY-RUN] {$sku} <= " . implode(', ', $selectedUrls) . "\n";
        $updated++;
        continue;
    }

    try {
        $product = $productRepository->get($sku, false, null, true);

        $gallery = $product->getMediaGalleryImages();
        if ($gallery) {
            foreach ($gallery as $image) {
                $file = $image->getFile();
                if ($file) {
                    $galleryProcessor->removeImage($product, $file);
                }
            }
        }

        foreach ($downloadedFiles as $index => $filePath) {
            $roles = $index === 0 ? ['image', 'small_image', 'thumbnail'] : [];
            $product->addImageToMediaGallery($filePath, $roles, false, false);
        }

        $productRepository->save($product);

        echo "[OK] {$sku} updated with " . count($downloadedFiles) . " image(s)\n";
        $updated++;
    } catch (Throwable $e) {
        echo "[FAIL] {$sku}: {$e->getMessage()}\n";
        $failed++;
    }
}

echo "\nDone. Updated: {$updated}, Skipped: {$skipped}, Failed: {$failed}\n";
echo "Tip: run bin/magento cache:flush if storefront images are cached.\n";
