<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Service class for stock data
 */
class Stock
{

    public const REQUIRE = [
        'entity_ids'
    ];

    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var array[]
     */
    private $entityIds;
    /**
     * @var ModuleManager
     */
    private $moduleManager;
    /**
     * @var string
     */
    private $linkField;

    /**
     * Stock constructor.
     * @param ResourceConnection $resource
     * @param ModuleManager $moduleManager
     * @param MetadataPool $metadataPool
     * @throws Exception
     */
    public function __construct(
        ResourceConnection $resource,
        ModuleManager $moduleManager,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->moduleManager = $moduleManager;
        $this->linkField = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * Get stock data
     *
     * @param array $productIds
     * @return array[]
     */
    public function execute(array $productIds = []): array
    {
        $this->setData('entity_ids', $productIds);
        return ($this->isMsiEnabled())
            ? $this->getMsiStock()
            : $this->getNoMsiStock();
    }

    /**
     * @param string $type
     * @param mixed $data
     */
    public function setData(string $type, $data)
    {
        if (!$data) {
            return;
        }
        if ($type == 'entity_ids') {
            $this->entityIds = $data;
        }
    }

    /**
     * Check is MSI enabled
     *
     * @return bool
     */
    private function isMsiEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }

    /**
     * Get stock qty for specified products if MSI enabled
     *
     * Structure of response
     * [product_id] => [
     *      qty
     *      is_in_stock
     *      reserved
     *      salable_qty
     *      ["msi"]=> [
     *          channel => [
     *              qty
     *              availability
     *              is_salable
     *              salable_qty
     *          ]
     *      ]
     * ]
     *
     * @return array[]
     */
    private function getMsiStock(): array
    {
        $channels = $this->getChannels();
        $stockData = $this->collectMsi($channels);
        $result = $this->getNoMsiStock(true);

        foreach ($stockData as $value) {
            foreach ($channels as $channel) {
                if (!array_key_exists($value['product_id'], $result)) {
                    continue;
                }

                $qty = $value[sprintf('quantity_%s', (int)$channel)];
                $reserved = max($result[$value['product_id']]['reserved'], 0);
                $salableQty = $qty - $reserved;

                $result[$value['product_id']]['msi'][$channel] = [
                    'qty' => (int)($value[sprintf('quantity_%s', (int)$channel)] ?? 0),
                    'is_salable' => $value[sprintf('is_salable_%s', (int)$channel)] ?? 0,
                    'availability' => $value[sprintf('is_salable_%s', (int)$channel)] ?? 0,
                    'salable_qty' => $salableQty ?: 0
                ];
            }
        }

        return $result;
    }

    /**
     * Get MSI stock channels
     *
     * @return array[]
     */
    private function getChannels(): array
    {
        $select = $this->resource->getConnection()->select()
            ->from(
                $this->resource->getTableName('inventory_stock_sales_channel'),
                ['stock_id']
            )
            ->where(
                'type = ?',
                'website'
            );
        return array_unique($this->resource->getConnection()->fetchCol($select));
    }

    /**
     * Collect MSI stock data
     *
     * @param array[] $channels
     * @return array[]
     */
    private function collectMsi(array $channels): array
    {
        $select = $this->resource->getConnection()->select()
            ->from(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                ['product_id' => 'entity_id', $this->linkField]
            )->where(
                'cpe.entity_id IN (?)',
                $this->entityIds
            );

        foreach ($channels as $channel) {
            $table = sprintf('inventory_stock_%s', (int)$channel);
            $select->joinLeft(
                [$table => $this->resource->getTableName($table)],
                "cpe.sku = {$table}.sku",
                [
                    sprintf('quantity_%s', (int)$channel) => "{$table}.quantity",
                    sprintf('is_salable_%s', (int)$channel) => "{$table}.is_salable"
                ]
            );
        }

        return $this->resource->getConnection()->fetchAll($select);
    }

    /**
     * Get stock qty for products without MSI
     *
     * Structure of response
     * [product_id] => [
     *      qty
     *      is_in_stock
     *      reserved
     *      salable_qty
     *      manage_stock
     *      qty_increments
     *      min_sale_qty
     * ]
     *
     * @param bool $addMsi
     * @return array[]
     */
    private function getNoMsiStock(bool $addMsi = false): array
    {
        $result = [];
        $select = $this->resource->getConnection()
            ->select()
            ->from(
                ['cataloginventory_stock_item' => $this->resource->getTableName('cataloginventory_stock_item')],
                [
                    'product_id',
                    'qty',
                    'is_in_stock',
                    'manage_stock',
                    'qty_increments',
                    'min_sale_qty'
                ]
            )->joinLeft(
                ['catalog_product_entity' => $this->resource->getTableName('catalog_product_entity')],
                "catalog_product_entity.entity_id = cataloginventory_stock_item.product_id",
                ['sku']
            )->joinLeft(
                ['css' => $this->resource->getTableName('cataloginventory_stock_status')],
                'css.product_id = catalog_product_entity.entity_id',
                ['stock_status']
            );
        if ($addMsi) {
            $select->joinLeft(
                ['inventory_reservation' => $this->resource->getTableName('inventory_reservation')],
                'inventory_reservation.sku = catalog_product_entity.sku',
                ['reserved' => 'SUM(COALESCE(inventory_reservation.quantity, 0))']
            );
        }
        $select->where('cataloginventory_stock_item.product_id IN (?)', $this->entityIds);
        $select->group('product_id');
        $values = $this->resource->getConnection()->fetchAll($select);

        foreach ($values as $value) {
            $result[$value['product_id']] =
                [
                    'qty' => (int)$value['qty'],
                    'is_in_stock' => (int)$value['stock_status'],
                    'availability' => (int)$value['stock_status'],
                    'manage_stock' => (int)$value['manage_stock'],
                    'qty_increments' => (int)$value['qty_increments'],
                    'min_sale_qty' => (int)$value['min_sale_qty']
                ];
            if ($addMsi) {
                $reserved = (int)$value['reserved'] * -1;
                $result[$value['product_id']] += [
                    'reserved' => $reserved,
                    'salable_qty' => (int)($value['qty'] - $reserved)
                ];
            }
        }
        return $result;
    }

    /**
     * @return string[]
     */
    public function getRequiredParameters(): array
    {
        return self::REQUIRE;
    }

    /**
     * @param string $type
     */
    public function resetData(string $type = 'all')
    {
        if ($type == 'all') {
            unset($this->entityIds);
        }
        if ($type == 'entity_ids') {
            unset($this->entityIds);
        }
    }
}
