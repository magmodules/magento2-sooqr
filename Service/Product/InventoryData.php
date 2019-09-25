<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Service\Product;

use Magento\Framework\App\ResourceConnection;

/**
 * Class InventoryData
 *
 * @package Magmodules\Sooqr\Service\Product
 */
class InventoryData
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * InventoryData constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $config
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function addDataToProduct($product, $config)
    {
        if (empty($config['inventory']['stock_id'])) {
            return $product;
        }

        /**
         * Return if product is not of simple type
         */
        if ($product->getTypeId() != 'simple') {
            return $product;
        }

        $inventoryData = $this->getInventoryData($product->getSku(), $config['inventory']['stock_id']);
        $isSalable = isset($inventoryData['is_salable']) ? $inventoryData['is_salable'] : 0;

        return $product->setIsSalable($isSalable)->setIsInStock($isSalable);
    }

    /**
     * @param $sku
     * @param $stockId
     *
     * @return array|void
     */
    private function getInventoryData($sku, $stockId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock_' . $stockId);

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $select = $connection->select()
            ->from($tableName)
            ->where('sku = ?', $sku)
            ->limit(1);

        if ($stockData = $connection->fetchRow($select)) {
            return $stockData;
        }
    }
}