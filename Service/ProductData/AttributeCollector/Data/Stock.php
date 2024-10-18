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
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

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
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var string
     */
    private $linkField;

    public function __construct(
        ResourceConnection $resource,
        ModuleManager $moduleManager,
        StoreManagerInterface $storeManager,
        WebsiteRepositoryInterface $websiteRepository,
        LogRepository $logRepository,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->moduleManager = $moduleManager;
        $this->storeManager = $storeManager;
        $this->websiteRepository = $websiteRepository;
        $this->logRepository = $logRepository;
        $this->linkField = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * Get stock data
     *
     * @param array $productIds
     * @param int $storeId
     * @return array[]
     */
    public function execute(array $productIds = [], int $storeId = 0): array
    {
        $this->setData('entity_ids', $productIds);
        return ($this->isMsiEnabled())
            ? $this->getMsiStock($storeId)
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
    private function getMsiStock(int $storeId): array
    {
        $stockId = $this->getStockId($storeId);
        $stockData = $this->collectMsi($stockId);
        $reservations = $this->collectMsiReservations($stockId);
        $result = $this->getNoMsiStock();

        foreach ($stockData as $value) {
            if (!array_key_exists($value['product_id'], $result)) {
                continue;
            }

            $quantity = (int)($value['quantity'] ?? 0);
            $reservations = max($reservations[$value['product_id']] ?? 0, 0);

            $result[$value['product_id']]['qty'] = $quantity;
            $result[$value['product_id']]['is_salable'] = $value['is_salable'] ?? 0;
            $result[$value['product_id']]['availability'] = $value['is_salable'] ?? 0;
            $result[$value['product_id']]['is_in_stock'] = $value['is_salable'] ?? 0;
            $result[$value['product_id']]['salable_qty'] = $quantity - $reservations;
        }

        return $result;
    }

    /**
     * Get MSI stock channels
     *
     * @param int $storeId
     * @return int
     */
    private function getStockId(int $storeId): int
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $website = $this->websiteRepository->getById($store->getWebsiteId());
            $select = $this->resource->getConnection()->select()
                ->from(
                    $this->resource->getTableName('inventory_stock_sales_channel'),
                    ['stock_id']
                )
                ->where(
                    'type = ?',
                    'website'
                )->where(
                    'code = ?',
                    $website->getCode()
                )->limit(1);
            return (int)$this->resource->getConnection()->fetchOne($select);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('MSI getStockId', $exception->getMessage());
            return 1;
        }
    }

    /**
     * Collect MSI stock data
     *
     * @param int $stockId
     * @return array[]
     */
    private function collectMsi(int $stockId): array
    {
        $stockView = sprintf('inventory_stock_%s', (int)$stockId);
        $select = $this->resource->getConnection()->select()
            ->from(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                ['product_id' => 'entity_id', $this->linkField]
            )->where(
                'cpe.entity_id IN (?)',
                $this->entityIds
            )->joinLeft(
                ['inv_stock' => $this->resource->getTableName($stockView)],
                "cpe.sku = inv_stock.sku",
                ['inv_stock.quantity', 'inv_stock.is_salable']
            );

        return $this->resource->getConnection()->fetchAll($select);
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function collectMsiReservations(int $stockId): array
    {
        $select = $this->resource->getConnection()->select()
            ->from(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                ['product_id' => 'entity_id']
            )->where(
                'cpe.entity_id IN (?)',
                $this->entityIds
            )->joinLeft(
                ['inv_res' => $this->resource->getTableName('inventory_reservation')],
                "inv_res.sku = cpe.sku AND inv_res.stock_id = {$stockId}",
                ['reserved' => 'SUM(COALESCE(inv_res.quantity, 0))']
            );

        return $this->resource->getConnection()->fetchPairs($select);
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
     * @return array[]
     */
    private function getNoMsiStock(): array
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
            )->where(
                'cataloginventory_stock_item.product_id IN (?)',
                $this->entityIds
            )->group(
                'product_id'
            );
        $values = $this->resource->getConnection()->fetchAll($select);

        foreach ($values as $value) {
            $result[$value['product_id']] = [
                'qty' => (int)$value['qty'],
                'is_in_stock' => (int)$value['stock_status'],
                'availability' => (int)$value['stock_status'],
                'manage_stock' => (int)$value['manage_stock'],
                'qty_increments' => (int)$value['qty_increments'],
                'min_sale_qty' => (int)$value['min_sale_qty']
            ];
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
