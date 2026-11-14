<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Test\Unit\Service\ProductData\AttributeCollector\Data;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data\Stock;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StockTest extends TestCase
{
    /**
     * Verify that MSI reservations (stored as negative quantities) reduce every product's salable
     * quantity, not only the first product in the stock data result set.
     */
    public function testExecuteSubtractsMsiReservationsForEveryProduct(): void
    {
        $msiStockData = [
            ['product_id' => 1, 'quantity' => 10, 'is_salable' => 1],
            ['product_id' => 2, 'quantity' => 20, 'is_salable' => 1],
            ['product_id' => 3, 'quantity' => 30, 'is_salable' => 1],
        ];

        $noMsiStockData = [
            ['product_id' => 1, 'qty' => 10, 'stock_status' => 1, 'manage_stock' => 1, 'qty_increments' => 1, 'min_sale_qty' => 1],
            ['product_id' => 2, 'qty' => 20, 'stock_status' => 1, 'manage_stock' => 1, 'qty_increments' => 1, 'min_sale_qty' => 1],
            ['product_id' => 3, 'qty' => 30, 'stock_status' => 1, 'manage_stock' => 1, 'qty_increments' => 1, 'min_sale_qty' => 1],
        ];

        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('joinLeft')->willReturnSelf();
        $groupedBy = [];
        $select->method('group')->willReturnCallback(
            function ($group) use (&$groupedBy, $select) {
                $groupedBy[] = $group;

                return $select;
            }
        );
        $select->method('limit')->willReturnSelf();

        $fetchAllCount = 0;
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturn('5');
        $connection->method('fetchPairs')->willReturn([1 => -3, 2 => -5, 3 => -7]);
        $connection->method('fetchAll')->willReturnCallback(
            function () use (&$fetchAllCount, $msiStockData, $noMsiStockData) {
                $fetchAllCount++;

                return $fetchAllCount === 1 ? $msiStockData : $noMsiStockData;
            }
        );

        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnCallback(
            function ($tableName) {
                return $tableName;
            }
        );

        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->with('Magento_Inventory')->willReturn(true);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(1);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->with(0)->willReturn($store);

        $website = $this->createMock(WebsiteInterface::class);
        $website->method('getCode')->willReturn('base');

        $websiteRepository = $this->createMock(WebsiteRepositoryInterface::class);
        $websiteRepository->method('getById')->with(1)->willReturn($website);

        $logRepository = $this->createMock(LogRepository::class);

        $metadata = $this->createMock(EntityMetadataInterface::class);
        $metadata->method('getLinkField')->willReturn('entity_id');

        $metadataPool = $this->createMock(MetadataPool::class);
        $metadataPool->method('getMetadata')->with(ProductInterface::class)->willReturn($metadata);

        $stock = new Stock(
            $resource,
            $moduleManager,
            $storeManager,
            $websiteRepository,
            $logRepository,
            $metadataPool
        );

        $result = $stock->execute([1, 2, 3], 0);

        $this->assertContains('cpe.entity_id', $groupedBy);
        $this->assertSame(10, $result[1]['qty']);
        $this->assertSame(7, $result[1]['salable_qty']);
        $this->assertSame(20, $result[2]['qty']);
        $this->assertSame(15, $result[2]['salable_qty']);
        $this->assertSame(30, $result[3]['qty']);
        $this->assertSame(23, $result[3]['salable_qty']);
    }
}
