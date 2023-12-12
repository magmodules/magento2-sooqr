<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\Delta;

use Exception;
use Magento\Framework\App\ResourceConnection;

/**
 * Set Delta products service class
 */
class Set
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Repository constructor.
     * @param ResourceConnection $resourceConnection
     * @throws Exception
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $ids
     */
    public function execute($ids)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        // get all id's that already in sooqr_delta table
        $indexedIds = $connection->fetchCol(
            $connection->select()->from(
                $this->resourceConnection->getTableName('sooqr_delta'),
                ['product_id']
            )
        );

        $select = $connection->select()->from(
            ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
            ['entity_id', 'updated_at']
        )->joinLeft(
            ['css' => $this->resourceConnection->getTableName('cataloginventory_stock_status')],
            'cpe.entity_id = css.product_id',
            ['stock_status']
        );

        if ($ids) {
            // fetch assoc array of requested product ids
            $dataArray = $connection->fetchAssoc(
                $select->where('cpe.entity_id in (?)', $ids)
            );
        } else {
            if ($indexedIds) {
                // fetch assoc array of product ids that not in the sooqr_delta table
                $dataArray = $connection->fetchAssoc(
                    $select->where('cpe.entity_id not in (?)', $indexedIds)
                );
            } else {
                // fetch assoc array of all products
                $dataArray = $connection->fetchAssoc(
                    $select
                );
            }
        }

        foreach ($dataArray as $data) {
            if (in_array($data['entity_id'], $indexedIds)) {
                $connection->update(
                    $this->resourceConnection->getTableName('sooqr_delta'),
                    [
                        'product_updated_at' => $data['updated_at'],
                        'stock_status' => $data['stock_status'] ?? 0
                    ],
                    ['product_id = ?' => $data['entity_id']]
                );
            } else {
                $connection->insert(
                    $this->resourceConnection->getTableName('sooqr_delta'),
                    [
                        'product_id' => $data['entity_id'],
                        'product_updated_at' => $data['updated_at'],
                        'stock_status' => $data['stock_status'] ?? 0
                    ]
                );
            }
        }
        $connection->commit();
    }
}
