<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data;

use Magento\Framework\App\ResourceConnection;

/**
 * Service class for product rating
 */
class Rating
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
     * Rating constructor.
     *
     * @param ResourceConnection $resource
     * @throws \Exception
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Get stock data
     *
     * @param array[] $entityIds
     * @return array[]
     */
    public function execute(array $entityIds = [], int $storeId = 0): array
    {
        $this->setData('entity_ids', $entityIds);

        $aggregateRatings = [];
        foreach ($this->getAggregateRatings($storeId) as $rating) {
            $aggregateRatings[$rating['product_id']] = $rating['rating'];
        }

        return $aggregateRatings;
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getAggregateRatings(int $storeId = 0): array
    {
        $table = $this->resource->getTableName('rating_option_vote_aggregated');
        $ratings = $this->resource->getConnection()->select()
            ->from($table, ['rating' => 'percent_approved', 'product_id' => 'entity_pk_value'])
            ->where("entity_pk_value IN (?)", $this->entityIds)
            ->where("store_id = ?", $storeId);

        return $this->resource->getConnection()->fetchAll($ratings);
    }

    /**
     * @param string $type
     * @param mixed $data
     */
    public function setData($type, $data)
    {
        if (!$data) {
            return;
        }
        switch ($type) {
            case 'entity_ids':
                $this->entityIds = $data;
                break;
        }
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
    public function resetData($type = 'all')
    {
        if ($type == 'all') {
            unset($this->entityIds);
        }
        if ($type == 'entity_ids') {
            unset($this->entityIds);
        }
    }
}
