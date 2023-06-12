<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Data class
 * Collecting products data according provided IDs and attributes to fetch
 * Return array with keys product IDs and values is arrays of required data
 */
class Data
{
    /**
     * @var AttributeCollector\Data\AttributeMapper
     */
    private $attributeMapper;
    /**
     * @var AttributeCollector\Data\Url
     */
    private $url;
    /**
     * @var AttributeCollector\Data\Category
     */
    private $category;
    /**
     * @var AttributeCollector\Data\Stock
     */
    private $stock;
    /**
     * @var AttributeCollector\Data\Price
     */
    private $price;
    /**
     * @var AttributeCollector\Data\Rating
     */
    private $rating;

    /**
     * Data constructor.
     * @param AttributeCollector\Data\AttributeMapper $attributeMapper
     * @param AttributeCollector\Data\Url $url
     * @param AttributeCollector\Data\Category $category
     * @param AttributeCollector\Data\Stock $stock
     * @param AttributeCollector\Data\Price $price
     * @param AttributeCollector\Data\Rating $rating
     */
    public function __construct(
        AttributeCollector\Data\AttributeMapper $attributeMapper,
        AttributeCollector\Data\Url $url,
        AttributeCollector\Data\Category $category,
        AttributeCollector\Data\Stock $stock,
        AttributeCollector\Data\Price $price,
        AttributeCollector\Data\Rating $rating
    ) {
        $this->attributeMapper = $attributeMapper;
        $this->url = $url;
        $this->category = $category;
        $this->stock = $stock;
        $this->price = $price;
        $this->rating = $rating;
    }

    /**
     * @param array $entityIds
     * @param array $attributeMap
     * @param array $extraParameters
     * @param int $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(array $entityIds, array $attributeMap, array $extraParameters, int $storeId = 0): array
    {
        $result = $this->attributeMapper->execute(
            $entityIds,
            $attributeMap,
            'catalog_product',
            $storeId
        );

        $data = [];
        foreach ($attributeMap as $targetCode => $attributeCode) {
            if (!isset($result[$attributeCode])) {
                continue;
            }
            foreach ($result[$attributeCode] as $entityId => $value) {
                $data[$entityId][$targetCode] = $value;
            }
        }

        $result = $this->url->execute(
            $entityIds,
            'product',
            $storeId
        );
        foreach ($result as $urlEntityId => $url) {
            $data[$urlEntityId]['url'] = $url;
        }

        $result = $this->category->execute(
            $entityIds,
            $storeId,
            'raw',
            $extraParameters
        );
        foreach ($result as $entityId => $categoryData) {
            $data[$entityId]['category'] = $categoryData;
        }

        if (!empty($extraParameters['rating_summary']['enabled'])) {
            $ratings = $this->rating->execute($entityIds, $storeId);
            foreach ($ratings as $entityId => $rating) {
                $data[$entityId]['rating_summary'] = $rating;
            }
        }

        if (!empty($extraParameters['stock']['inventory'])) {
            $result = $this->stock->execute(
                $entityIds
            );

            $inventoryFields = $extraParameters['stock']['inventory_fields'];
            foreach ($result as $entityId => $stockData) {
                $data[$entityId] += array_intersect_key($stockData, array_flip($inventoryFields));
            }
        }

        $result = $this->price->execute(
            $entityIds,
            $extraParameters['behaviour']['grouped']['price_logic'] ?? 'max',
            $extraParameters['behaviour']['bundle']['price_logic'] ?? 'min',
            $storeId
        );
        foreach ($result as $entityId => $priceData) {
            $data[$entityId] += $priceData;
        }

        return $data;
    }
}
