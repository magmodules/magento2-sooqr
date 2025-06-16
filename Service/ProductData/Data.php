<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

class Data
{

    private AttributeCollector\Data\AttributeMapper $attributeMapper;
    private AttributeCollector\Data\Url $url;
    private AttributeCollector\Data\Category $category;
    private AttributeCollector\Data\Stock $stock;
    private AttributeCollector\Data\Price $price;
    private AttributeCollector\Data\Rating $rating;
    private AttributeCollector\Data\SuperAttribute $superAttribute;
    private ResourceConnection $resourceConnection;

    private string $linkField;
    private array $entityIds = [];
    private int $storeId = 0;
    private array $rowIds = [];
    private array $productIds = [];
    private array $extraParameters = [];

    public function __construct(
        AttributeCollector\Data\AttributeMapper $attributeMapper,
        AttributeCollector\Data\Url $url,
        AttributeCollector\Data\Category $category,
        AttributeCollector\Data\Stock $stock,
        AttributeCollector\Data\Price $price,
        AttributeCollector\Data\Rating $rating,
        AttributeCollector\Data\SuperAttribute $superAttribute,
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->attributeMapper = $attributeMapper;
        $this->url = $url;
        $this->category = $category;
        $this->stock = $stock;
        $this->price = $price;
        $this->superAttribute = $superAttribute;
        $this->rating = $rating;
        $this->resourceConnection = $resourceConnection;
        $this->linkField = $metadataPool->getMetadata(CategoryInterface::class)->getLinkField();
    }

    /**
     * Collect product data based on IDs and attributes.
     *
     * @param array $entityIds Product entity IDs to process.
     * @param array $attributeMap Mapping of attributes to collect.
     * @param array $extraParameters Additional parameters for processing.
     * @param int $storeId Store ID context.
     * @return array Collected product data.
     */
    public function execute(array $entityIds, array $attributeMap, array $extraParameters, int $storeId = 0): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $this->entityIds = $entityIds;
        $this->storeId = $storeId;
        $this->extraParameters = $extraParameters;
        $this->rowIds = $this->getRowsIds($entityIds);
        $this->productIds = array_flip($this->rowIds);

        $data = [];

        $this->mergeAttributeData($data, $attributeMap);
        $this->mergeUrlData($data);
        $this->mergeCategoryData($data);
        $this->mergeStockData($data);
        $this->mergePriceData($data);
        $this->mergeSuperAttributeData($data);
        $this->mergeRatingData($data);

        return $data;
    }

    /**
     * Get row IDs for the provided entity IDs.
     *
     * @param array $entityIds Product entity IDs.
     * @return array Mapped row IDs with entity IDs as keys.
     */
    private function getRowsIds(array $entityIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_entity');

        $select = $connection->select()
            ->from($table, ['entity_id', $this->linkField])
            ->where("{$this->linkField} IN (?)", $entityIds);

        return $connection->fetchPairs($select);
    }

    /**
     * Merge attribute data into the main result set.
     *
     * @param array $data Existing data array.
     * @param array $attributeMap Mapping of attributes to collect.
     * @return void
     */
    private function mergeAttributeData(array &$data, array $attributeMap): void
    {
        $result = $this->attributeMapper->execute($this->entityIds, $attributeMap, 'catalog_product', $this->storeId);

        foreach ($attributeMap as $targetCode => $attributeCode) {
            if (!isset($result[$attributeCode])) {
                continue;
            }
            foreach ($result[$attributeCode] as $entityId => $value) {
                $data[$entityId][$targetCode] = $value;
            }
        }
    }

    /**
     * Merge URL data into the main result set.
     *
     * @param array $data Existing data array.
     * @return void
     */
    private function mergeUrlData(array &$data): void
    {
        $result = $this->url->execute($this->entityIds, 'product', $this->storeId);

        foreach ($result as $urlEntityId => $url) {
            $data[$urlEntityId]['url'] = $url;
        }
    }

    /**
     * Merge category data into the main result set.
     *
     * @param array $data Existing data array.
     * @return void
     */
    private function mergeCategoryData(array &$data): void
    {
        $result = $this->category->execute($this->productIds, $this->storeId, 'raw', $this->extraParameters);

        foreach ($result as $productId => $categoryData) {
            $data[$this->rowIds[$productId]]['category'] = $categoryData;
        }
    }

    /**
     * Merge stock data into the main result set.
     *
     * @param array $data Existing data array.
     * @return void
     */
    private function mergeStockData(array &$data): void
    {
        if (!$this->extraParameters['stock']['inventory']) {
            return;
        }

        $result = $this->stock->execute($this->productIds, $this->storeId);
        $inventoryFields = array_merge(
            $this->extraParameters['stock']['inventory_fields'],
            ['qty', 'msi', 'salable_qty', 'reserved', 'is_in_stock']
        );

        foreach ($result as $productId => $stockData) {
            $data[$this->rowIds[$productId]] += array_intersect_key($stockData, array_flip($inventoryFields));
        }
    }

    /**
     * Merge price data into the main result set.
     *
     * @param array $data Existing data array.
     * @return void
     */
    private function mergePriceData(array &$data): void
    {
        $result = $this->price->execute(
            $this->productIds,
            $this->extraParameters['behaviour']['grouped']['price_logic'] ?? 'max',
            $this->extraParameters['behaviour']['bundle']['price_logic'] ?? 'min',
            $this->storeId
        );

        foreach ($result as $productId => $priceData) {
            $data[$this->rowIds[$productId]] += $priceData;
        }
    }

    /**
     * Merge super attribute labels into the main result set.
     * This method collects labels for configurable attributes (e.g., color, size)
     * for parent products and merges them into the data array based on the
     * mapping of entity IDs to row IDs.
     *
     * @param array $data Existing data array keyed by row ID.
     * @return void
     */
    private function mergeSuperAttributeData(array &$data): void
    {
        if (empty($this->extraParameters['behaviour']['configurable']['add_super_attributes'])) {
            return;
        }

        $result = $this->superAttribute->execute($this->entityIds, $this->storeId);
        foreach ($result as $productId => $superAttributes) {
            if (!isset($this->rowIds[$productId])) {
                continue;
            }
            $data[$this->rowIds[$productId]] += $superAttributes;
        }
    }

    /**
     * Merge product rating summary into the main result set.
     * If enabled via extraParameters, this method retrieves and merges the rating summary
     * per product into the data array, using row ID as the key.
     *
     * @param array $data Existing data array keyed by row ID.
     * @return void
     */
    private function mergeRatingData(array &$data): void
    {
        if (empty($this->extraParameters['rating_summary']['enabled'])) {
            return;
        }

        $ratings = $this->rating->execute($this->entityIds, $this->storeId);
        foreach ($ratings as $productId => $rating) {
            if (!isset($this->rowIds[$productId])) {
                continue;
            }
            $data[$this->rowIds[$productId]]['rating_summary'] = $rating;
        }
    }
}
