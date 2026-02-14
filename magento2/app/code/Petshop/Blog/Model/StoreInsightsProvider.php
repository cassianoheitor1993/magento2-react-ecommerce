<?php
namespace Petshop\Blog\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class StoreInsightsProvider
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    public function getInsights(): array
    {
        $storeName = (string) $this->storeManager->getStore()->getName();
        $storeName = $this->normalizeStoreName($storeName);

        return [
            'store_name' => $storeName,
            'categories' => $this->getCategoryNames(),
            'sales_summary_30d' => $this->getSalesSummary(),
            'top_selling_products_30d' => $this->getTopSellingProducts()
        ];
    }

    private function normalizeStoreName(string $storeName): string
    {
        $value = trim($storeName);
        if ($value === '') {
            return 'Petshop';
        }

        $genericNames = [
            'default store view',
            'main website store',
            'default',
            'store view'
        ];

        if (in_array(strtolower($value), $genericNames, true)) {
            return 'Petshop';
        }

        return $value;
    }

    private function getCategoryNames(): array
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('level', ['gt' => 1])
            ->setPageSize(20);

        $names = [];
        foreach ($collection as $category) {
            $name = trim((string) $category->getName());
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    private function getSalesSummary(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');

        if (!$connection->isTableExists($orderTable)) {
            return [
                'orders_count' => 0,
                'revenue_base' => 0.0
            ];
        }

        $select = $connection->select()
            ->from(
                ['o' => $orderTable],
                [
                    'orders_count' => new \Zend_Db_Expr('COUNT(*)'),
                    'revenue_base' => new \Zend_Db_Expr('COALESCE(SUM(o.base_grand_total), 0)')
                ]
            )
            ->where('o.created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)')
            ->where('o.state NOT IN (?)', ['canceled']);

        $result = (array) $connection->fetchRow($select);

        return [
            'orders_count' => (int) ($result['orders_count'] ?? 0),
            'revenue_base' => (float) ($result['revenue_base'] ?? 0)
        ];
    }

    private function getTopSellingProducts(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderItemTable = $this->resourceConnection->getTableName('sales_order_item');
        $orderTable = $this->resourceConnection->getTableName('sales_order');

        if (!$connection->isTableExists($orderItemTable) || !$connection->isTableExists($orderTable)) {
            return [];
        }

        $select = $connection->select()
            ->from(['i' => $orderItemTable], [
                'sku' => 'i.sku',
                'name' => 'i.name',
                'qty_ordered' => new \Zend_Db_Expr('COALESCE(SUM(i.qty_ordered), 0)')
            ])
            ->joinInner(['o' => $orderTable], 'o.entity_id = i.order_id', [])
            ->where('o.created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)')
            ->where('o.state NOT IN (?)', ['canceled'])
            ->where('i.parent_item_id IS NULL')
            ->group(['i.sku', 'i.name'])
            ->order('qty_ordered DESC')
            ->limit(8);

        $rows = (array) $connection->fetchAll($select);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'sku' => (string) ($row['sku'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'qty_ordered' => (float) ($row['qty_ordered'] ?? 0)
            ];
        }

        return $items;
    }
}
