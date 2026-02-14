<?php

declare(strict_types=1);

namespace Petshop\Marketing\Model\Service;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class CampaignInsightsProvider
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getInsights(): array
    {
        return [
            'generated_at_utc' => gmdate('Y-m-d H:i:s'),
            'store_name' => (string) $this->storeManager->getStore()->getName(),
            'product_highlights' => $this->getProductHighlights(),
            'inventory_summary' => $this->getInventorySummary(),
            'sales_summary_30d' => $this->getSalesSummary(),
            'top_selling_products_30d' => $this->getTopSellingProducts(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProductHighlights(): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'status', 'visibility'])
            ->addAttributeToFilter('status', 1)
            ->setPageSize(12)
            ->setCurPage(1);

        $items = [];
        foreach ($collection as $product) {
            $items[] = [
                'sku' => (string) $product->getSku(),
                'name' => (string) $product->getName(),
                'price' => (float) $product->getPrice(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function getInventorySummary(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $inventorySourceItem = $this->resourceConnection->getTableName('inventory_source_item');
        if ($connection->isTableExists($inventorySourceItem)) {
            $summary = (array) $connection->fetchRow(
                $connection->select()->from(['i' => $inventorySourceItem], [
                    'total_skus' => new \Zend_Db_Expr('COUNT(DISTINCT i.sku)'),
                    'total_qty' => new \Zend_Db_Expr('COALESCE(SUM(i.quantity), 0)'),
                    'low_stock_skus' => new \Zend_Db_Expr('SUM(CASE WHEN i.status = 1 AND i.quantity > 0 AND i.quantity <= 5 THEN 1 ELSE 0 END)'),
                    'out_of_stock_skus' => new \Zend_Db_Expr('SUM(CASE WHEN i.status = 0 OR i.quantity <= 0 THEN 1 ELSE 0 END)')
                ])
            );

            return [
                'source' => 'inventory_source_item',
                'total_skus' => (int) ($summary['total_skus'] ?? 0),
                'total_qty' => (float) ($summary['total_qty'] ?? 0),
                'low_stock_skus' => (int) ($summary['low_stock_skus'] ?? 0),
                'out_of_stock_skus' => (int) ($summary['out_of_stock_skus'] ?? 0),
            ];
        }

        $stockItemTable = $this->resourceConnection->getTableName('cataloginventory_stock_item');
        if ($connection->isTableExists($stockItemTable)) {
            $summary = (array) $connection->fetchRow(
                $connection->select()->from(['s' => $stockItemTable], [
                    'total_skus' => new \Zend_Db_Expr('COUNT(*)'),
                    'total_qty' => new \Zend_Db_Expr('COALESCE(SUM(s.qty), 0)'),
                    'low_stock_skus' => new \Zend_Db_Expr('SUM(CASE WHEN s.is_in_stock = 1 AND s.qty > 0 AND s.qty <= 5 THEN 1 ELSE 0 END)'),
                    'out_of_stock_skus' => new \Zend_Db_Expr('SUM(CASE WHEN s.is_in_stock = 0 OR s.qty <= 0 THEN 1 ELSE 0 END)')
                ])
            );

            return [
                'source' => 'cataloginventory_stock_item',
                'total_skus' => (int) ($summary['total_skus'] ?? 0),
                'total_qty' => (float) ($summary['total_qty'] ?? 0),
                'low_stock_skus' => (int) ($summary['low_stock_skus'] ?? 0),
                'out_of_stock_skus' => (int) ($summary['out_of_stock_skus'] ?? 0),
            ];
        }

        return [
            'source' => 'unavailable',
            'total_skus' => 0,
            'total_qty' => 0.0,
            'low_stock_skus' => 0,
            'out_of_stock_skus' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSalesSummary(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');

        if (!$connection->isTableExists($orderTable)) {
            return [
                'orders_count' => 0,
                'revenue_base' => 0.0,
                'avg_ticket_base' => 0.0,
            ];
        }

        $result = (array) $connection->fetchRow(
            $connection->select()
                ->from(['o' => $orderTable], [
                    'orders_count' => new \Zend_Db_Expr('COUNT(*)'),
                    'revenue_base' => new \Zend_Db_Expr('COALESCE(SUM(o.base_grand_total), 0)'),
                ])
                ->where('o.created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)')
                ->where('o.state NOT IN (?)', ['canceled'])
        );

        $orders = (int) ($result['orders_count'] ?? 0);
        $revenue = (float) ($result['revenue_base'] ?? 0.0);

        return [
            'orders_count' => $orders,
            'revenue_base' => $revenue,
            'avg_ticket_base' => $orders > 0 ? round($revenue / $orders, 2) : 0.0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTopSellingProducts(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderItemTable = $this->resourceConnection->getTableName('sales_order_item');
        $orderTable = $this->resourceConnection->getTableName('sales_order');

        if (!$connection->isTableExists($orderItemTable) || !$connection->isTableExists($orderTable)) {
            return [];
        }

        $rows = (array) $connection->fetchAll(
            $connection->select()
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
                ->limit(8)
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'sku' => (string) ($row['sku'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'qty_ordered' => (float) ($row['qty_ordered'] ?? 0),
            ];
        }

        return $items;
    }
}
