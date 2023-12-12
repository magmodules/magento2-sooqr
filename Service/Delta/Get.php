<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\Delta;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magmodules\Sooqr\Model\Config\Source\FeedType;

/**
 * Get Delta service class
 */
class Get
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
     * @param int $storeId
     * @return array
     */
    public function execute(int $storeId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $deltaSelect = $connection->select()->from(
            $this->resourceConnection->getTableName('sooqr_delta'),
            'product_id'
        );

        if ($date = $this->getLastUpdatedAt($storeId)) {
            $deltaSelect->where('updated_at > ?', $date);
        } else {
            return [];
        }

        $delta = $connection->fetchCol($deltaSelect);
        return array_unique($delta);
    }

    /**
     * Get the latest saved feed run by storeId
     *
     * @param int $storeId
     * @return string
     */
    private function getLastUpdatedAt(int $storeId): string
    {
        $connection = $this->resourceConnection->getConnection();
        $deltaSelect = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('sooqr_feed'),
                'started_at'
            )->where(
                'store_id = ?',
                $storeId
            )->where(
                'type != ?',
                FeedType::PREVIEW
            )->order(
                'started_at DESC'
            );

        return $connection->fetchOne($deltaSelect);
    }
}
