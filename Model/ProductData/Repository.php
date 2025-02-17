<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\ProductData;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\ProductData\RepositoryInterface as ProductData;
use Magmodules\Sooqr\Model\Config\Source\FeedType;
use Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data\Image;
use Magmodules\Sooqr\Service\ProductData\Filter;
use Magmodules\Sooqr\Service\ProductData\Type;

/**
 * Selftest repository class
 */
class Repository implements ProductData
{

    public const ATTRIBUTES = [
        'name',
        'sku',
        'description',
        'brand'
    ];

    private array $attributeMap = [
        'product_id' => 'entity_id',
        'visibility' => 'visibility',
        'type_id' => 'type_id',
        'status' => 'status'
    ];

    private array $resultMap = [
        'sqr:content_type' => 'content_type',
        'sqr:id' => 'product_id',
        'sqr:title' => 'name',
        'sqr:sku' => 'sku',
        'sqr:link' => 'url',
        'sqr:description' => 'description',
        'sqr:image_link' => 'image',
        'sqr:normal_price' => 'price',
        'sqr:normal_price_ex' => 'price_ex',
        'sqr:price' => 'final_price',
        'sqr:price_ex' => 'final_price_ex',
        'sqr:currency' => 'currency',
        'sqr:product_object_type' => 'type_id',
        'sqr:status' => 'status',
        'sqr:visibility' => 'visibility',
        'sqr:availability' => 'is_in_stock',
        'sqr:assoc_id' => 'parent_id',
        'sqr:is_bundle' => 'is_bundle',
        'sqr:is_parent' => 'is_parent',
        'sqr:salable_qty' => 'salable_qty',
        'sqr:qty' => 'qty',
        'sqr:is_in_stock' => 'is_in_stock',
        'sqr:rating' => 'rating_summary'
    ];

    private array $entityIds;
    private array $parentSimples;
    private array $staticFields;
    private array $imageData;

    private Type $type;
    private Filter $filter;
    private Image $image;
    private ConfigProvider $configProvider;
    private FilterManager$filterManager;
    private Json $json;

    public function __construct(
        ConfigProvider $configProvider,
        Json $json,
        FilterManager $filterManager,
        Filter $filter,
        Type $type,
        Image $image
    ) {
        $this->configProvider = $configProvider;
        $this->json = $json;
        $this->filterManager = $filterManager;
        $this->filter = $filter;
        $this->type = $type;
        $this->image = $image;
    }

    /**
     * @inheritDoc
     */
    public function getProductData(int $storeId = 0, ?array $entityIds = null, int $type = FeedType::FULL): array
    {
        $this->collectIds($storeId, $entityIds);
        $this->collectAttributes($storeId);
        $this->parentSimples = [];
        $this->staticFields = $this->configProvider->getStaticFields($storeId);

        $result = [];

        $totalIds = count($this->entityIds);
        $batchSize = $this->configProvider->getBatchSize();
        $batches = $type !== FeedType::FULL ? 1 : (int) ceil($totalIds / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $batchIds = array_slice($this->entityIds, $batch * $batchSize, $batchSize);
            if (empty($batchIds)) {
                continue;
            }

            $this->imageData = $this->image->execute($batchIds, $storeId);
            foreach ($this->collectProductData($storeId, $batchIds) as $entityId => $productData) {
                if (empty($productData['product_id']) || $productData['status'] == 2) {
                    continue;
                }
                $this->addImageData($storeId, (int)$entityId, $productData);
                $this->addStaticFields($productData);
                foreach ($this->resultMap as $index => $attr) {
                    $result[$entityId][$index] = $this->prepareAttribute($attr, $productData);
                }
                $result[$entityId] += $this->categoryData($productData);

                if (!empty($productData['parent_id'])) {
                    $this->parentSimples[$productData['parent_id']][] = $productData['product_id'];
                }
            }
        }

        return $this->postProcess($result, $storeId);
    }

    /**
     * Collect all entity ids for collection
     *
     * @param int $storeId
     * @param array|null $entityIds
     */
    private function collectIds(int $storeId, ?array $entityIds = null): void
    {
        $this->entityIds = $this->filter->execute(
            $this->configProvider->getFilters($storeId),
            $storeId
        );
        if ($entityIds !== null) {
            $this->entityIds = array_intersect($entityIds, $this->entityIds);
        }
    }

    /**
     * Collect all attributes needed for product collection
     *
     * @param int $storeId
     */
    private function collectAttributes(int $storeId = 0): void
    {
        $attributes = $this->configProvider->getAttributes($storeId);
        $this->attributeMap += $attributes;

        $extraAttributes = array_diff_key($attributes, array_flip(self::ATTRIBUTES));
        $this->resultMap += array_combine(array_map(function ($key) {
            return 'sqr:' . $key;
        }, array_keys($extraAttributes)), $extraAttributes);

        $this->attributeMap = array_filter($this->attributeMap);
    }

    /**
     * Collect all product data
     *
     * @param int $storeId
     * @param array $batchIds
     * @return array
     * @throws NoSuchEntityException
     */
    private function collectProductData(int $storeId, array $batchIds): array
    {
        $extraParameters = [
            'filters' => [
                'exclude_attribute' => null,
                'exclude_disabled' => !$this->configProvider->getFilters($storeId)['add_disabled_products'],
                'custom' => []
            ],
            'stock' => [
                'inventory' => true,
                'inventory_fields' => ['qty', 'is_in_stock', 'salable_qty']
            ],
            'rating_summary' => [
                'enabled' => $this->configProvider->addRatingSummary($storeId),
            ],
            'category' => [
                'exclude_attribute' => ['code' => 'sooqr_cat_disable_export', 'value' => 1],
                'include_anchor' => true
            ],
            'behaviour' => [
                'configurable' => $this->configProvider->getConfigProductsBehaviour($storeId),
                'bundle' => $this->configProvider->getBundleProductsBehaviour($storeId),
                'grouped' => $this->configProvider->getGroupedProductsBehaviour($storeId)
            ]
        ];

        return $this->type->execute(
            $batchIds,
            $this->attributeMap,
            $extraParameters,
            $storeId
        );
    }

    /**
     * Add image data to productData array
     *
     * @param int $storeId
     * @param int $entityId
     * @param array $productData
     */
    private function addImageData(int $storeId, int $entityId, array &$productData): void
    {
        $imageData = $this->imageData[$entityId] ?? null;
        if (!empty($productData['parent_id']) && !empty($productData['image_logic'])) {
            $parentImageData = $this->imageData[$productData['parent_id']] ?? null;
            switch ($productData['image_logic']) {
                case 1:
                    $imageData = $parentImageData;
                    break;
                case 2:
                    $imageData = $imageData ?? $parentImageData;
                    break;
                case 3:
                case 4:
                    foreach ($parentImageData as $imgStoreId => $parentImageDataStore) {
                        $imageData[$imgStoreId] += $parentImageDataStore;
                    }
                    break;
            }
        }

        $productData['image'] = $this->getImageWithFallback($imageData, [$storeId, 0]);
    }

    /**
     * @param array|null $imageData
     * @param array $storeIds
     * @return string|null
     */
    private function getImageWithFallback(?array $imageData, array $storeIds): ?string
    {
        if ($imageData === null) {
            return null;
        }

        $imageSource = $this->configProvider->getImageAttribute($storeIds[0]);
        foreach ($storeIds as $storeId) {
            if (!isset($imageData[$storeId])) {
                continue;
            }
            ksort($imageData[$storeId]);
            foreach ($imageData[$storeId] as $image) {
                if (in_array($imageSource, $image['types'])) {
                    return $image['file'];
                }
            }
        }

        foreach ($storeIds as $storeId) {
            if (!empty($imageData[0][$storeId]['file'])) {
                return $imageData[0][$storeId]['file'];
            }
        }

        return $imageData[0][0]['file'] ?? null;
    }

    /**
     * Add static data to productData array
     *
     * @param array $productData
     */
    private function addStaticFields(array &$productData): void
    {
        foreach ($this->staticFields as $k => $v) {
            if (!is_array($v)) {
                $productData[$k] = $v;
                continue;
            }

            foreach ($v as $kk => $vv) {
                list($attribute, $condition, $value) = explode(' ', $kk);
                if (isset($productData[$attribute])) {
                    $attributeValue = $productData[$attribute];
                    switch ($condition) {
                        case '==':
                            $value = ($attributeValue == $value) ? $vv : null;
                            break;
                        case '!=':
                            $value = ($attributeValue != $value) ? $vv : null;
                            break;
                        case '>=':
                            $value = ($attributeValue >= $value) ? $vv : null;
                            break;
                        case '>':
                            $value = ($attributeValue > $value) ? $vv : null;
                            break;
                        case '<=':
                            $value = ($attributeValue <= $value) ? $vv : null;
                            break;
                        case '<':
                            $value = ($attributeValue < $value) ? $vv : null;
                            break;
                        case 'between':
                            list($from, $to) = explode('/', $value);
                            $value = ($attributeValue >= $from && $attributeValue <= $to) ? $vv : null;
                            break;
                        case 'default':
                            $value = $vv;
                            break;
                    }
                    if ($value !== null) {
                        $productData[$k] = $value;
                    }
                }
            }
        }
    }

    /**
     * Attribute data preparation
     *
     * @param string $attribute
     * @param array $productData
     * @return mixed|string|null
     */
    private function prepareAttribute(string $attribute, array $productData)
    {
        $value = $productData[$attribute] ?? null;
        switch ($attribute) {
            case 'parent_id':
                if (!isset($productData['parent_id'])) {
                    return $productData['product_id'];
                } else {
                    return $productData['parent_id'];
                }
                break;
            case 'status':
                return ($value == 1) ? 'Enabled' : 'Disabled';
            case 'is_in_stock':
                return ($value) ? 'in stock' : 'out of stock';
            case 'manage_stock':
                return ($value) ? 'true' : 'false';
            case 'url':
                return $productData['url'] ?? '';
            case 'description':
                return $this->reformatDescription((string)$value);
            case 'price':
            case 'price_ex':
            case 'final_price':
            case 'final_price_ex':
            case 'min_price':
            case 'max_price':
            case 'sales_price':
                if ($value !== null) {
                    return number_format((float)$value, 2, '.', '');
                }
            // no break
            case 'visibility':
                switch ($value) {
                    case 1:
                        return 'Not Visible Individually';
                    case 2:
                        return 'Catalog';
                    case 3:
                        return 'Search';
                    case 4:
                        return 'Catalog, Search';
                }
            // no break
            case 'qty':
            case 'salable_qty':
                if (($productData['type_id'] == 'configurable') && $value === 0) {
                    return null;
                }
            // no break
            default:
                return $value;
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function reformatDescription(string $value): string
    {
        if (strpos($value, "[mgz_pagebuilder]") === 0) {
            try {
                $pattern = '/\[mgz_pagebuilder\](.*?)\[\/mgz_pagebuilder\]/s';
                if (preg_match($pattern, $value, $matches)) {
                    $content = $this->json->unserialize($matches[1]);
                    $found = $this->findAllContentInArray($content, 'content');
                    $value = implode(' ', $found);
                }
            } catch (\Exception $exception) {
                return $this->filterManager->removeTags($value);
            }
        }

        return $this->filterManager->removeTags($value);
    }

    /**
     * @param array $array
     * @param string|null $key
     * @return array
     */
    private function findAllContentInArray(array $array, ?string $key = null): array
    {
        array_walk_recursive($array, function ($v, $k) use ($key, &$val) {
            if ($key === null || ($key && $k == $key)) {
                $val[] = $v;
            }
        });

        return array_unique($val ?? []);
    }

    /**
     * Add category data to productData array
     *
     * @param array $productData
     * @return array
     */
    private function categoryData(array $productData): array
    {
        $categoryData = [];
        if (empty($productData['category'])) {
            return $categoryData;
        }

        foreach ($productData['category'] as $category) {
            $categoryNames = explode(' > ', $category['path']);
            foreach ($categoryNames as $k => $categoryName) {
                $key = $k + 1;
                $categoryData["sqr:category{$key}"][] = $categoryName;
            }
        }

        return $categoryData;
    }

    /**
     * @param array $result
     * @param int $storeId
     * @return array
     */
    private function postProcess(array $result, int $storeId = 0): array
    {
        if (!$this->configProvider->getFilters($storeId)['exclude_out_of_stock'] || empty($result)) {
            return $result;
        }

        $unsetSimples = [];
        foreach ($result as $id => &$row) {

            // Remove parent products without simples
            if ($row['sqr:id'] == $row['sqr:assoc_id'] && $row['sqr:price'] == 0.00) {
                $unsetSimples[] = $id;
                continue;
            }

            // Remove out of stock products
            if ($row['sqr:availability'] != 'out of stock') {
                continue;
            }
            $unsetSimples[] = $id;

            if (!empty($row['sqr:assoc_id']) && isset($this->parentSimples[$row['sqr:assoc_id']])) {
                $this->parentSimples[$row['sqr:assoc_id']] = array_diff(
                    $this->parentSimples[$row['sqr:assoc_id']],
                    [$id]
                );
            }
        }

        $emptyParents = array_keys(array_filter($this->parentSimples), function ($value) {
            return empty($value);
        });

        return array_diff_key($result, array_flip($emptyParents) + array_flip($unsetSimples));
    }

    /**
     * @inheritDoc
     */
    public function getProductAttributes(int $storeId = 0): array
    {
        $this->collectAttributes($storeId);
        return $this->attributeMap;
    }
}
