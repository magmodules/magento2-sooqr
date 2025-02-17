<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;

class Filter
{

    private ResourceConnection $resourceConnection;
    private StoreManagerInterface $storeManager;
    private string $entityId;
    private ?int $statusAttributeId = null;
    private ?int $visibilityAttributeId = null;

    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->entityId = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * Execute filters and return product entity ids
     *
     * @param array $filter
     * @param int $storeId
     * @return array
     */
    public function execute(array $filter, int $storeId = 0): array
    {
        $this->prefetchAttributeIds();

        $entityIds = $this->filterVisibility($filter, $storeId);
        $entityIds = $this->filterStatus($entityIds, $filter['add_disabled_products'], $storeId);

        $websiteId = $storeId ? $this->getWebsiteId($storeId) : null;
        return $this->filterByWebsiteAndCategory(
            $entityIds,
            $websiteId,
            $filter['category_restriction_behaviour'],
            $filter['category']
        );
    }

    /**
     * Prefetch and cache attribute IDs for `status` and `visibility`
     */
    private function prefetchAttributeIds(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $eavTable = $this->resourceConnection->getTableName('eav_attribute');

        $attributes = $connection->fetchPairs(
            $connection->select()
                ->from($eavTable, ['attribute_code', 'attribute_id'])
                ->where('entity_type_id = ?', 4)
                ->where('attribute_code IN (?)', ['status', 'visibility'])
        );

        $this->statusAttributeId = (int)($attributes['status'] ?? 0);
        $this->visibilityAttributeId = (int)($attributes['visibility'] ?? 0);
    }

    /**
     * Filter entity IDs to exclude products based on visibility.
     *
     * @param array $filter
     * @param int $storeId
     * @return array
     */
    private function filterVisibility(array $filter, int $storeId = 0): array
    {
        if (!$this->visibilityAttributeId) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $visibility = $filter['filter_by_visibility']
            ? (is_array($filter['visibility']) ? $filter['visibility'] : explode(',', $filter['visibility']))
            : [
                Visibility::VISIBILITY_NOT_VISIBLE,
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ];

        // Convert visibility values to integers (ensures SQL compatibility)
        $visibility = array_map('intval', $visibility);

        // Get entity IDs from global store (store_id = 0)
        $selectBase = $connection->select()
            ->distinct()
            ->from(['cpei' => $table], [$this->entityId])
            ->where('cpei.attribute_id = ?', $this->visibilityAttributeId)
            ->where('cpei.value IN (?)', $visibility)
            ->where('cpei.store_id = ?', 0);

        // Get entity IDs where store-specific visibility overrides global setting
        $selectStore = $connection->select()
            ->distinct()
            ->from(['cpei' => $table], [$this->entityId])
            ->where('cpei.attribute_id = ?', $this->visibilityAttributeId)
            ->where('cpei.value NOT IN (?)', $visibility)
            ->where('cpei.store_id = ?', $storeId);

        return array_diff(
            $connection->fetchCol($selectBase),
            $connection->fetchCol($selectStore)
        );
    }

    /**
     * Filter entity IDs to exclude products with status disabled efficiently.
     *
     * @param array $entityIds
     * @param bool $addDisabled
     * @param int $storeId
     * @return array
     */
    private function filterStatus(array $entityIds, bool $addDisabled = false, int $storeId = 0): array
    {
        if (empty($entityIds) || $addDisabled || !$this->statusAttributeId) {
            return $entityIds;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $select = $connection->select()
            ->from(['cpei' => $table], [$this->entityId])
            ->where('cpei.attribute_id = ?', $this->statusAttributeId)
            ->where('cpei.value = ?', Status::STATUS_ENABLED)
            ->where('cpei.store_id IN (?)', [0, $storeId])
            ->order('cpei.store_id ASC');

        return array_intersect($entityIds, $connection->fetchCol($select));
    }

    /**
     * @param int $storeId
     * @return int
     */
    private function getWebsiteId(int $storeId = 0): int
    {
        try {
            return (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        } catch (Exception $exception) {
            return 0;
        }
    }

    /**
     * Filter entity IDs based on website and category restrictions
     *
     * @param array $entityIds
     * @param int $websiteId
     * @param string|null $categoryBehaviour ('in' or 'not_in')
     * @param array|null $categoryIds
     * @return array
     */
    private function filterByWebsiteAndCategory(
        array $entityIds,
        int $websiteId,
        ?string $categoryBehaviour = null,
        ?array $categoryIds = null
    ): array {
        if (empty($entityIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $cpeTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $cpwTable = $this->resourceConnection->getTableName('catalog_product_website');
        $ccpTable = $this->resourceConnection->getTableName('catalog_category_product');

        $select = $connection->select()
            ->distinct()
            ->from(['cpe' => $cpeTable], [$this->entityId])
            ->join(['cpw' => $cpwTable], 'cpe.entity_id = cpw.product_id', [])
            ->where('cpw.website_id = ?', $websiteId)
            ->where("cpe.{$this->entityId} IN (?)", $entityIds);

        if (!empty($categoryIds) && $categoryBehaviour !== null) {
            $select->join(['ccp' => $ccpTable], 'cpe.entity_id = ccp.product_id', []);
            if ($categoryBehaviour === 'in') {
                $select->where('ccp.category_id IN (?)', $categoryIds);
            } else {
                $select->where('ccp.category_id NOT IN (?)', $categoryIds);
            }
        }

        return $connection->fetchCol($select);
    }
}
