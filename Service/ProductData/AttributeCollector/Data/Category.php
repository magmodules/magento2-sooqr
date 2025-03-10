<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data;

use Exception;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Api\StoreRepositoryInterface;

class Category
{
    public const REQUIRE = ['entity_ids', 'store_id'];

    private ResourceConnection $resource;
    private StoreRepositoryInterface $storeRepository;
    private array $entityIds = [];
    private int $storeId;
    private string $format;
    private array $categoryNames = [];
    private array $excludedCategories = [];
    private array $excludeAttribute = [];
    private string $linkField;
    private string $customAttribute = '';
    private array $categoryIds = [];
    private bool $includeAnchor = false;

    public function __construct(
        ResourceConnection $resource,
        StoreRepositoryInterface $storeRepository,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->storeRepository = $storeRepository;
        $this->linkField = $metadataPool->getMetadata(CategoryInterface::class)->getLinkField();
    }

    /**
     * Executes the collection of category paths with names and custom attribute values.
     *
     * @param array $entityIds Array of product IDs.
     * @param int $storeId Current store ID.
     * @param string $format Format of the return data ('raw' or other).
     * @param array $extraParameters Additional parameters (keys: custom, exclude_attribute, include_anchor, add_url).
     * @return array
     */
    public function execute(
        array $entityIds = [],
        int $storeId = 0,
        string $format = 'raw',
        array $extraParameters = []
    ): array {
        $this->entityIds = $entityIds;
        $this->storeId = $storeId;
        $this->format = $format;
        $this->customAttribute = $extraParameters['category']['custom'] ?? '';
        $this->includeAnchor = $extraParameters['category']['include_anchor'] ?? false;
        $this->excludeAttribute = $extraParameters['category']['exclude_attribute'] ?? [];

        $this->collectCategoryNames();
        if (!empty($this->excludeAttribute)) {
            $this->collectExcluded();
        }

        $data = $this->collectCategories();
        $data = $this->mergeNames($data);

        if (!empty($extraParameters['category']['add_url'])) {
            return $this->mergeUrl($data);
        }

        // Sort each product's categories by level (descending).
        foreach ($data as &$productCats) {
            array_multisort(array_column($productCats, 'level'), SORT_DESC, $productCats);
        }

        return $data;
    }

    /**
     * Collects category names and custom attribute values from the database.
     *
     * @return void
     */
    private function collectCategoryNames(): void
    {
        $connection = $this->resource->getConnection();
        $attributes = $this->customAttribute ? ['name', $this->customAttribute] : ['name'];
        $varCharTable = $this->resource->getTableName('catalog_category_entity_varchar');

        $select = $connection->select()
            ->from(
                ['eav_attribute' => $this->resource->getTableName('eav_attribute')],
                ['attribute_id', 'attribute_code']
            )
            ->joinLeft(
                ['catalog_category_entity_varchar' => $varCharTable],
                'catalog_category_entity_varchar.attribute_id = eav_attribute.attribute_id',
                ['entity_id' => $this->linkField, 'value', 'store_id']
            )
            ->where('eav_attribute.attribute_code IN (?)', $attributes)
            ->where('catalog_category_entity_varchar.store_id IN (?)', [0, $this->storeId]);

        foreach ($connection->fetchAll($select) as $item) {
            if (!$item['value']) {
                continue;
            }
            if (!isset($this->categoryNames[$item['entity_id']][$item['store_id']])) {
                $this->categoryNames[$item['entity_id']][$item['store_id']] = [];
            }
            $this->categoryNames[$item['entity_id']][$item['store_id']][$item['attribute_code']] = $item['value'];
        }
    }

    /**
     * Collects excluded category IDs based on the provided attribute configuration.
     *
     * @return void
     */
    private function collectExcluded(): void
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['eav_attribute' => $this->resource->getTableName('eav_attribute')],
                ['attribute_code']
            )
            ->joinLeft(
                ['catalog_category_entity_int' => $this->resource->getTableName('catalog_category_entity_int')],
                'catalog_category_entity_int.attribute_id = eav_attribute.attribute_id',
                ['entity_id' => $this->linkField, 'value', 'store_id']
            )
            ->where('eav_attribute.attribute_code = ?', $this->excludeAttribute['code'])
            ->where('catalog_category_entity_int.store_id IN (?)', [0, $this->storeId]);

        foreach ($connection->fetchAll($select) as $item) {
            if ($item['value'] == $this->excludeAttribute['value']) {
                $this->excludedCategories[$item['store_id']][] = $item['entity_id'];
            }
        }
    }

    /**
     * Collects category paths assigned to products.
     *
     * @return array
     */
    private function collectCategories(): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['catalog_category_product' => $this->resource->getTableName('catalog_category_product')],
                'product_id'
            )
            ->joinLeft(
                ['catalog_category_entity' => $this->resource->getTableName('catalog_category_entity')],
                "catalog_category_entity.{$this->linkField} = catalog_category_product.category_id",
                ['path', 'parent_id']
            )
            ->where('product_id IN (?)', $this->entityIds);

        if (!empty($this->excludedCategories)) {
            $select->where("catalog_category_entity.{$this->linkField} NOT IN (?)", $this->excludedCategories);
        }

        $result = $connection->fetchAll($select);
        $paths = [];
        $parentIds = [];

        foreach ($result as $item) {
            $paths[$item['product_id']][] = $item['path'];
            $parentIds[] = $item['parent_id'];
        }

        if ($this->includeAnchor) {
            $this->addAnchorCategories($paths, array_unique($parentIds));
        }

        return $paths;
    }

    /**
     * Appends anchor category paths (parent categories marked as anchors) to product paths.
     *
     * @param array &$paths Reference to array of category paths.
     * @param array $parentIds Array of parent category IDs.
     * @return void
     */
    private function addAnchorCategories(array &$paths, array $parentIds): void
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['catalog_category_entity' => $this->resource->getTableName('catalog_category_entity')],
                ['entity_id' => $this->linkField, 'path']
            )
            ->joinLeft(
                ['catalog_category_entity_int' => $this->resource->getTableName('catalog_category_entity_int')],
                "catalog_category_entity_int.{$this->linkField} = catalog_category_entity.{$this->linkField}",
                []
            )
            ->joinLeft(
                ['eav_attribute' => $this->resource->getTableName('eav_attribute')],
                'catalog_category_entity_int.' . $this->linkField . ' = catalog_category_entity.entity_id',
                []
            )
            ->where('eav_attribute.attribute_code = ?', 'is_anchor')
            ->where('catalog_category_entity_int.value = ?', 1)
            ->where('catalog_category_entity.entity_id IN (?)', $parentIds);

        if (!empty($this->excludedCategories)) {
            $select->where('catalog_category_entity.entity_id NOT IN (?)', $this->excludedCategories);
        }

        $anchorPaths = $connection->fetchPairs($select);

        foreach ($paths as $productId => $pathArray) {
            foreach ($pathArray as $singlePath) {
                if ($singlePath === null) {
                    continue;
                }

                $categoryIds = explode('/', $singlePath);
                array_pop($categoryIds);
                $diff = array_intersect(array_keys(array_flip($categoryIds)), array_keys($anchorPaths));
                foreach ($diff as $catId) {
                    if (!in_array($anchorPaths[$catId], $paths[$productId], true)) {
                        $paths[$productId][] = $anchorPaths[$catId];
                    }
                }
            }
        }
    }

    /**
     * Merges category paths with their names and custom attribute values.
     *
     * For each product and each category path (after removing the root category),
     * this function builds a "name" path and uses fallback logic to get a custom attribute value.
     *
     * @param array $data Array of category paths.
     * @return array
     */
    private function mergeNames(array $data): array
    {
        $result = [];
        $rootCategoryId = $this->getRootCategoryId();

        foreach ($data as $entityId => $categoryPaths) {
            $usedPaths = [];
            foreach ($categoryPaths as $categoryPath) {
                if (!$categoryPath) {
                    continue;
                }
                $categoryIds = explode('/', $categoryPath);
                $rootKey = array_search($rootCategoryId, $categoryIds);
                if ($rootKey === false) {
                    continue;
                }
                // Work only on categories below the root.
                $categoryIds = array_slice($categoryIds, $rootKey + 1);
                $level = count($categoryIds);
                if ($level === 0) {
                    continue;
                }
                $fullPath = $categoryIds;
                $namesForPath = [];
                $realId = 0;
                foreach ($categoryIds as $categoryId) {
                    if (!isset($this->categoryNames[$categoryId])) {
                        continue;
                    }
                    $namesForPath[] = $this->getCategoryAttribute((int)$categoryId, 'name');
                    $realId = (int)$categoryId;
                    if (!in_array($categoryId, $this->categoryIds, true)) {
                        $this->categoryIds[] = $categoryId;
                    }
                }
                if ($this->format === 'raw') {
                    do {
                        $pathString = implode(' > ', $namesForPath);
                        $customForPath = $this->getCustomForTruncatedPath($fullPath, count($namesForPath));
                        if (!in_array($pathString, $usedPaths, true)) {
                            $result[$entityId][] = [
                                'level' => $level,
                                'name' => $this->getCategoryAttribute($realId, 'name'),
                                'custom' => end($customForPath),
                                'path' => $pathString,
                                'category_id' => $realId,
                            ];
                        }
                        $usedPaths[] = $pathString;
                        array_pop($namesForPath);
                        $level--;
                    } while ($level > 0);
                } else {
                    $result[$entityId][] = implode(' > ', $namesForPath);
                }
            }
        }

        return $result;
    }

    /**
     * Retrieves the root category ID for the current store.
     *
     * @return int|null
     */
    private function getRootCategoryId(): ?int
    {
        try {
            return (int)$this->storeRepository->getById($this->storeId)->getRootCategoryId();
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * Retrieves a category's attribute value for the current store, with a fallback to store 0.
     *
     * @param int $categoryId
     * @param string $attribute
     * @return string
     */
    private function getCategoryAttribute(int $categoryId, string $attribute): string
    {
        return $this->categoryNames[$categoryId][$this->storeId][$attribute]
            ?? $this->categoryNames[$categoryId][0][$attribute]
            ?? '';
    }

    /**
     * Returns an array of custom attribute values for the truncated path.
     * If a category does not have a custom value, a fallback from deeper categories is used.
     *
     * @param array $fullPath Array of category IDs (after the root).
     * @param int $truncatedCount Count of elements in the truncated path.
     * @return array
     */
    private function getCustomForTruncatedPath(array $fullPath, int $truncatedCount): array
    {
        $customValues = [];
        $fullPathCount = count($fullPath);

        for ($i = 0; $i < $truncatedCount; $i++) {
            $catId = $fullPath[$i];
            $custom = $this->getCategoryAttribute((int)$catId, $this->customAttribute);

            if ($custom === '' && $this->customAttribute) {
                for ($j = $i + 1; $j < $fullPathCount; $j++) {
                    $fallback = $this->getCategoryAttribute((int)$fullPath[$j], $this->customAttribute);
                    if ($fallback !== '') {
                        $custom = $fallback;
                        break;
                    }
                }
            }
            $customValues[] = $custom;
        }

        return array_filter($customValues, static fn ($val) => $val !== '');
    }

    /**
     * Merges URL information into the category data.
     *
     * @param array $data Category data array.
     * @return array
     */
    private function mergeUrl(array $data): array
    {
        try {
            $baseUrl = $this->storeRepository->getById($this->storeId)->getBaseUrl();
        } catch (Exception $exception) {
            $baseUrl = '';
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['catalog_category_entity' => $this->resource->getTableName('catalog_category_entity')],
                [$this->linkField]
            )
            ->join(
                ['url_rewrite' => $this->resource->getTableName('url_rewrite')],
                'catalog_category_entity.entity_id = url_rewrite.entity_id',
                ['request_path']
            )
            ->where("catalog_category_entity.{$this->linkField} IN (?)", $this->categoryIds)
            ->where('entity_type = ?', 'category');

        $urls = $connection->fetchAll($select);
        foreach ($data as &$datum) {
            foreach ($datum as &$item) {
                $key = array_search($item['category_id'], array_column($urls, 'entity_id'));
                if ($key !== false && isset($urls[$key]['request_path'])) {
                    $item['url'] = $baseUrl . $urls[$key]['request_path'];
                }
            }
        }
        return $data;
    }

    /**
     * Returns the required parameters.
     *
     * @return string[]
     */
    public function getRequiredParameters(): array
    {
        return self::REQUIRE;
    }
}
