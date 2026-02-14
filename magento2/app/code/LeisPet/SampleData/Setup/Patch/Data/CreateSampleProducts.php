<?php
namespace LeisPet\SampleData\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class CreateSampleProducts implements DataPatchInterface
{
    protected ProductInterfaceFactory $productFactory;
    protected ProductRepositoryInterface $productRepository;
    protected State $appState;
    protected StoreManagerInterface $storeManager;
    protected EavSetup $eavSetup;
    protected CategoryLinkManagementInterface $categoryLink;
    protected SourceItemInterfaceFactory $sourceItemFactory;
    protected SourceItemsSaveInterface $sourceItemsSave;

    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        State $appState,
        StoreManagerInterface $storeManager,
        EavSetup $eavSetup,
        CategoryLinkManagementInterface $categoryLink,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->eavSetup = $eavSetup;
        $this->categoryLink = $categoryLink;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    public function apply()
    {
        $this->appState->emulateAreaCode(
            \Magento\Framework\App\Area::AREA_ADMINHTML,
            [$this, 'createProducts']
        );
    }

    public function createProducts()
    {
        $products = [
            [
                'sku' => 'pet-toy-001',
                'name' => 'Premium Chew Toy for Dogs',
                'price' => 15.99,
                'categories' => [3, 4], // Default Category (3) + New Arrivals (update IDs)
                'description' => 'Durable and safe chew toy for all dog breeds. Made from non-toxic rubber.',
                'is_new' => true
            ],
            [
                'sku' => 'pet-food-001',
                'name' => 'Organic Cat Food - Salmon Flavor',
                'price' => 24.99,
                'categories' => [3], // Catalog
                'description' => 'Premium organic cat food with real salmon. No artificial preservatives.',
                'is_new' => false
            ],
            [
                'sku' => 'pet-bed-001',
                'name' => 'Luxury Pet Bed - Medium Size',
                'price' => 45.00,
                'categories' => [3, 4], // Catalog + New Arrivals
                'description' => 'Comfortable orthopedic pet bed with removable washable cover.',
                'is_new' => true
            ],
            [
                'sku' => 'pet-collar-001',
                'name' => 'LED Safety Collar for Pets',
                'price' => 12.99,
                'categories' => [3, 4], // Catalog + New Arrivals
                'description' => 'Rechargeable LED collar for nighttime visibility. Adjustable size.',
                'is_new' => true
            ],
            [
                'sku' => 'pet-treats-001',
                'name' => 'Natural Dog Treats - Chicken Jerky',
                'price' => 8.99,
                'categories' => [3], // Catalog
                'description' => 'All-natural chicken jerky treats. No fillers or by-products.',
                'is_new' => false
            ],
            [
                'sku' => 'pet-grooming-001',
                'name' => 'Professional Pet Grooming Kit',
                'price' => 34.99,
                'categories' => [3], // Catalog
                'description' => 'Complete grooming kit with brushes, nail clippers, and scissors.',
                'is_new' => false
            ]
        ];

        foreach ($products as $productData) {
            try {
                /** @var Product $product */
                $product = $this->productFactory->create();
                $product->setSku($productData['sku']);
                $product->setName($productData['name']);
                $product->setAttributeSetId(4); // Default attribute set
                $product->setStatus(Status::STATUS_ENABLED);
                $product->setVisibility(Visibility::VISIBILITY_BOTH);
                $product->setTypeId(Type::TYPE_SIMPLE);
                $product->setPrice($productData['price']);
                $product->setWebsiteIds([1]); // Default website
                $product->setStoreId(0);
                
                // Set descriptions
                $product->setCustomAttribute('description', $productData['description']);
                $product->setCustomAttribute('short_description', $productData['description']);
                
                // Set stock
                $product->setStockData([
                    'use_config_manage_stock' => 0,
                    'manage_stock' => 1,
                    'is_in_stock' => 1,
                    'qty' => 100
                ]);

                // Save product
                $savedProduct = $this->productRepository->save($product);

                // Assign categories
                $this->categoryLink->assignProductToCategories(
                    $productData['sku'],
                    $productData['categories']
                );

                // Set inventory for MSI (Magento 2.3+)
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSourceCode('default');
                $sourceItem->setSku($productData['sku']);
                $sourceItem->setQuantity(100);
                $sourceItem->setStatus(1);
                $this->sourceItemsSave->execute([$sourceItem]);

            } catch (\Exception $e) {
                // Continue with next product if one fails
                continue;
            }
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
