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
 * Service class for category path for products
 */
class Parents
{

    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var string
     */
    private $linkField;

    /**
     * Category constructor.
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
     * Get array of products with parent IDs and types
     *
     * Structure of response
     *
     * @param array[] $entityIds array of product IDs
     * @return array[]
     */
    public function execute($entityIds = [], bool $excludeDisabled = true): array
    {
        if (empty($entityIds)) {
            return $this->collectAllParents($excludeDisabled);
        }
        return $this->collectParents($entityIds, $excludeDisabled);
    }

    /**
     * Get parent product IDs
     *
     * @return array[]
     */
    private function collectAllParents(bool $excludeDisabled): array
    {
        $result = [];
        $select = $this->resource->getConnection()
            ->select()
            ->from(
                ['catalog_product_relation' => $this->resource->getTableName('catalog_product_relation')]
            )->joinLeft(
                ['catalog_product_entity' => $this->resource->getTableName('catalog_product_entity')],
                "catalog_product_entity.{$this->linkField} = catalog_product_relation.parent_id",
                'type_id'
            );

        if ($excludeDisabled && $attributeId = $this->getStatusAttributeId()) {
            $select->joinLeft(
                ['catalog_product_entity_int' => $this->resource->getTableName('catalog_product_entity_int')],
                "catalog_product_entity_int.entity_id = catalog_product_entity.{$this->linkField}"
            )->where(
                'catalog_product_entity_int.value = ?',
                Status::STATUS_ENABLED
            )->where(
                'catalog_product_entity_int.attribute_id = ?',
                $attributeId
            );
        }

        foreach ($this->resource->getConnection()->fetchAll($select) as $item) {
            $result[$item['child_id']][$item['parent_id']] = $item['type_id'];
        }

        return $result;
    }

    /**
     * Get parent products IDs
     *
     * @param array[] $entityIds array of product IDs
     * @return array[]
     */
    private function collectParents(array $entityIds, bool $excludeDisabled): array
    {
        $all = $entityIds;
        $result = [];
        $select = $this->resource->getConnection()
            ->select()
            ->from(
                ['catalog_product_relation' => $this->resource->getTableName('catalog_product_relation')]
            )->joinLeft(
                ['catalog_product_entity' => $this->resource->getTableName('catalog_product_entity')],
                "catalog_product_entity.{$this->linkField} = catalog_product_relation.parent_id",
                'type_id'
            )->where('child_id IN (?)', $entityIds);

        if ($excludeDisabled && $attributeId = $this->getStatusAttributeId()) {
            $select->joinLeft(
                ['catalog_product_entity_int' => $this->resource->getTableName('catalog_product_entity_int')],
                "catalog_product_entity_int.entity_id = catalog_product_entity.{$this->linkField}"
            )->where(
                'catalog_product_entity_int.value = ?',
                Status::STATUS_ENABLED
            )->where(
                'catalog_product_entity_int.attribute_id = ?',
                $attributeId
            );
        }

        foreach ($this->resource->getConnection()->fetchAll($select) as $item) {
            $result[$item['child_id']][$item['parent_id']] = $item['type_id'];
            $all += [$item['child_id'], $item['parent_id']];
        }

        return ['all' => array_unique($all), 'relations' => $result];
    }

    /**
     * Get attribute id for status attribute
     *
     * @return int
     */
    private function getStatusAttributeId(): int
    {
        $connection = $this->resource->getConnection();
        $selectAttributeId = $connection->select()->from(
            ['eav_attribute' => $this->resource->getTableName('eav_attribute')],
            ['attribute_id']
        )->joinLeft(
            ['eav_entity_type' => $this->resource->getTableName('eav_entity_type')],
            'eav_entity_type.entity_type_id = eav_attribute.entity_type_id',
            []
        )->where(
            'entity_type_code = ?',
            'catalog_product'
        )->where(
            'attribute_code = ?',
            'status'
        );

        return (int)$connection->fetchOne($selectAttributeId);
    }
}
