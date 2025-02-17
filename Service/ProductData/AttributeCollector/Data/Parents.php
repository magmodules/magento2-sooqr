<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Service class for retrieving parent products.
 */
class Parents
{
    private ResourceConnection $resource;
    private string $linkField;
    private ?int $statusAttributeId = null;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resource
     * @param MetadataPool $metadataPool
     * @throws Exception
     */
    public function __construct(
        ResourceConnection $resource,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->linkField = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * Execute parent product collection.
     *
     * @param array $entityIds Array of product IDs.
     * @param bool $excludeDisabled Whether to exclude disabled parent products.
     * @return array[]
     */
    public function execute(array $entityIds = [], bool $excludeDisabled = true): array
    {
        $this->statusAttributeId = $this->fetchStatusAttributeId();

        return empty($entityIds)
            ? $this->collectAllParents($excludeDisabled)
            : $this->collectParents($entityIds, $excludeDisabled);
    }

    /**
     * Get all parent product IDs.
     *
     * @param bool $excludeDisabled Whether to exclude disabled parent products.
     * @return array[]
     */
    private function collectAllParents(bool $excludeDisabled): array
    {
        $connection = $this->resource->getConnection();
        $result = [];

        $cprTable = $this->resource->getTableName('catalog_product_relation');
        $cpeTable = $this->resource->getTableName('catalog_product_entity');
        $cpeiTable = $this->resource->getTableName('catalog_product_entity_int');

        $select = $connection->select()
            ->from(['cpr' => $cprTable], ['child_id', 'parent_id'])
            ->join(
                ['cpe' => $cpeTable],
                "cpe.{$this->linkField} = cpr.parent_id",
                ['type_id' => new \Zend_Db_Expr("COALESCE(cpe.type_id, 'simple')")]
            );

        if ($excludeDisabled && $this->statusAttributeId) {
            $select->join(
                ['cpei' => $cpeiTable],
                "cpei.{$this->linkField} = cpe.{$this->linkField} AND cpei.attribute_id = {$this->statusAttributeId}",
                []
            )->where('cpei.value = ?', Status::STATUS_ENABLED);
        }

        foreach ($connection->fetchAll($select) as $item) {
            $result[$item['child_id']][$item['parent_id']] = $item['type_id'];
        }

        return $result;
    }

    /**
     * Get parent product IDs for a specific list of entities.
     *
     * @param array $entityIds Array of product IDs.
     * @param bool $excludeDisabled Whether to exclude disabled parent products.
     * @return array[]
     */
    private function collectParents(array $entityIds, bool $excludeDisabled): array
    {
        $connection = $this->resource->getConnection();
        $result = [];

        $cprTable = $this->resource->getTableName('catalog_product_relation');
        $cpeTable = $this->resource->getTableName('catalog_product_entity');
        $cpeiTable = $this->resource->getTableName('catalog_product_entity_int');

        $select = $connection->select()
            ->from(['cpr' => $cprTable], ['child_id', 'parent_id'])
            ->join(
                ['cpe' => $cpeTable],
                "cpe.{$this->linkField} = cpr.parent_id",
                ['type_id' => new \Zend_Db_Expr("COALESCE(cpe.type_id, 'simple')")]
            )
            ->where('cpr.child_id IN (?)', $entityIds);

        if ($excludeDisabled && $this->statusAttributeId) {
            $select->join(
                ['cpei' => $cpeiTable],
                "cpei.{$this->linkField} = cpe.{$this->linkField} AND cpei.attribute_id = {$this->statusAttributeId}",
                []
            )->where('cpei.value = ?', Status::STATUS_ENABLED);
        }

        foreach ($connection->fetchAll($select) as $item) {
            $result[$item['child_id']][$item['parent_id']] = $item['type_id'];
        }

        return $result;
    }

    /**
     * Fetch the `status` attribute ID for products.
     *
     * @return int|null
     */
    private function fetchStatusAttributeId(): ?int
    {
        $connection = $this->resource->getConnection();
        $eavTable = $this->resource->getTableName('eav_attribute');

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($eavTable, ['attribute_id'])
                ->where('entity_type_id = ?', 4)
                ->where('attribute_code = ?', 'status')
                ->limit(1)
        ) ?: null;
    }
}
