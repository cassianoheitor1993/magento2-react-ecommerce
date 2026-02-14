<?php
namespace LeisPet\Blog\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class SeedPosts implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $table = $this->moduleDataSetup->getTable('leispet_blog_post');
        $connection = $this->moduleDataSetup->getConnection();

        $rows = [
            [
                'title' => 'Healthy Feeding Routine for Dogs and Cats',
                'slug' => 'healthy-feeding-routine-dogs-cats',
                'excerpt' => 'Build a simple daily meal routine that improves digestion, hydration and long-term health for your pets.',
                'content' => '<h2>Why routine matters</h2><p>Pets thrive on consistency. A regular feeding schedule supports digestion and reduces anxiety around meals.</p><h3>Daily checklist</h3><ul><li>Feed at the same times each day</li><li>Measure portions by weight</li><li>Keep fresh water available</li><li>Store dry food in airtight containers</li></ul><p><strong>Pro tip:</strong> Introduce food changes gradually over 7–10 days to avoid stomach upset.</p>',
                'author' => 'Dr. Emma Silva',
                'tags' => 'nutrition,dogs,cats,feeding',
                'is_published' => 1
            ],
            [
                'title' => 'Top 7 Training Rewards Pets Actually Love',
                'slug' => 'top-7-training-rewards-pets-love',
                'excerpt' => 'Use reward timing, treat size and variety to improve recall, sit, stay and leash behavior faster.',
                'content' => '<h2>Reward quality beats quantity</h2><p>Small, high-value rewards keep sessions focused. Rotate textures and flavors to keep motivation high.</p><ol><li>Soft training bites</li><li>Freeze-dried treats</li><li>Tiny jerky pieces</li><li>Interactive praise + treat combo</li></ol><p>Keep sessions short: 5 to 10 minutes works best.</p>',
                'author' => 'Lucas Meyer',
                'tags' => 'training,treats,dogs,cats',
                'is_published' => 1
            ],
            [
                'title' => 'Bird Nutrition Basics: Seeds, Pellets and Fresh Foods',
                'slug' => 'bird-nutrition-basics',
                'excerpt' => 'A practical feeding framework for parakeets and small parrots with better variety and micronutrient balance.',
                'content' => '<h2>Balanced bird plates</h2><p>Use pellets as a base, then add controlled portions of seeds and fresh produce.</p><ul><li>Pellets: 60–70%</li><li>Fresh foods: 20–30%</li><li>Seeds/treats: 10%</li></ul><p>Remove leftovers after a few hours to maintain hygiene.</p>',
                'author' => 'Ava Rodrigues',
                'tags' => 'birds,nutrition,pellets',
                'is_published' => 1
            ],
            [
                'title' => 'How to Pick the Best Food Container for Pet Freshness',
                'slug' => 'best-food-container-pet-freshness',
                'excerpt' => 'Container material, seal quality and storage location directly impact food aroma and shelf life.',
                'content' => '<h2>Container buying guide</h2><p>Choose BPA-free containers with strong silicone seals and easy-clean interiors.</p><h3>What to check</h3><ul><li>Odor-resistant build</li><li>Airtight lid test</li><li>Scooper compartment</li><li>Stackability for small spaces</li></ul>',
                'author' => 'LeisPet Editorial',
                'tags' => 'storage,food-containers,care',
                'is_published' => 1
            ],
            [
                'title' => 'Seasonal Promos You Can Combine for Better Savings',
                'slug' => 'seasonal-promos-combine-savings',
                'excerpt' => 'Learn how to combine featured deals, clearance items and new-pet bundles without sacrificing quality.',
                'content' => '<h2>Smart shopping strategy</h2><p>Start with essentials from Shop, then add promo bundles for treats and accessories.</p><p><em>Always compare unit price, not just sticker discount.</em></p>',
                'author' => 'LeisPet Deals Team',
                'tags' => 'promos,savings,bundles',
                'is_published' => 1
            ]
        ];

        foreach ($rows as $row) {
            $existingId = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['post_id'])
                    ->where('slug = ?', $row['slug'])
                    ->limit(1)
            );

            if ($existingId) {
                $connection->update($table, $row, ['post_id = ?' => (int) $existingId]);
            } else {
                $connection->insert($table, $row);
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