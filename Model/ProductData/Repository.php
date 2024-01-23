<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\ProductData;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\FilterManager;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as DataConfigRepository;
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

    /**
     * Base attributes map to pull from product
     *
     * @var array
     */
    private $attributeMap = [
        'product_id' => 'entity_id',
        'visibility' => 'visibility',
        'type_id' => 'type_id',
        'status' => 'status'
    ];

    /**
     * Base map of feed structure data. Values as magento data, keys as data for feed
     *
     * @var array
     */
    private $resultMap = [
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

    /**
     * @var DataConfigRepository
     */
    private $dataConfigRepository;
    /**
     * @var array
     */
    private $entityIds;
    /**
     * @var Type
     */
    private $type;
    /**
     * @var Filter
     */
    private $filter;
    /**
     * @var Image
     */
    private $image;
    /**
     * @var array
     */
    private $staticFields;
    /**
     * @var array
     */
    private $imageData;
    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * Repository constructor.
     * @param DataConfigRepository $dataConfigRepository
     * @param FilterManager $filterManager
     * @param Filter $filter
     * @param Type $type
     * @param Image $image
     */
    public function __construct(
        DataConfigRepository $dataConfigRepository,
        FilterManager $filterManager,
        Filter $filter,
        Type $type,
        Image $image
    ) {
        $this->dataConfigRepository = $dataConfigRepository;
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
        $this->staticFields = $this->dataConfigRepository->getStaticFields($storeId);
        $this->imageData = $this->image->execute($this->entityIds, $storeId);

        $result = [];
        foreach ($this->collectProductData($storeId, $type) as $entityId => $productData) {
            if (empty($productData['product_id'])) {
                continue;
            }
            $this->addImageData($storeId, (int)$entityId, $productData);
            $this->addStaticFields($productData);
            foreach ($this->resultMap as $index => $attr) {
                $result[$entityId][$index] = $this->prepareAttribute($attr, $productData);
            }
            $result[$entityId] += $this->categoryData($productData);
        }

        if ($this->dataConfigRepository->getFilters($storeId)['exclude_out_of_stock']) {
            foreach ($result as $id => &$row) {
                if ($row['sqr:availability'] == 'out of stock') {
                    unset($result[$id]);
                }
            }
        }

        return $result;
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
            $this->dataConfigRepository->getFilters($storeId),
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
        $attributes = $this->dataConfigRepository->getAttributes($storeId);
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
     * @param int $type
     * @return array
     * @throws NoSuchEntityException
     */
    private function collectProductData(int $storeId, int $type = 3): array
    {
        $extraParameters = [
            'filters' => [
                'exclude_attribute' => null,
                'exclude_disabled' => !$this->dataConfigRepository->getFilters($storeId)['add_disabled_products'],
                'custom' => []
            ],
            'stock' => [
                'inventory' => true,
                'inventory_fields' => ['qty', 'is_in_stock', 'salable_qty']
            ],
            'rating_summary' => [
                'enabled' => $this->dataConfigRepository->addRatingSummary($storeId),
            ],
            'category' => [
                'exclude_attribute' => ['code' => 'sooqr_cat_disable_export', 'value' => 1],
                'include_anchor' => true
            ],
            'behaviour' => [
                'configurable' => $this->dataConfigRepository->getConfigProductsBehaviour($storeId),
                'bundle' => $this->dataConfigRepository->getBundleProductsBehaviour($storeId),
                'grouped' => $this->dataConfigRepository->getGroupedProductsBehaviour($storeId)
            ]
        ];

        return $this->type->execute(
            $this->entityIds,
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

        if ($imageData === null) {
            return;
        }

        $imageSource = $this->dataConfigRepository->getImageAttribute($storeId);
        if (!isset($imageData[$storeId])) {
            $storeId = 0;
        }

        ksort($imageData[$storeId]);
        foreach ($imageData[$storeId] as $image) {
            if (in_array($imageSource, $image['types'])) {
                $productData['image'] = $image['file'];
            }
        }
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
     * Attribute data preperation
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
                return ($value) ? 'Enabled' : 'Disabled';
            case 'is_in_stock':
                return ($value) ? 'in stock' : 'out of stock';
            case 'manage_stock':
                return ($value) ? 'true' : 'false';
            case 'url':
                return $productData['url'] ?? '';
            case 'description':
                return $this->filterManager->removeTags((string)$value);
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
        $i = 1;
        foreach ($productData['category'] as $category) {
            $path = explode(' > ', $category['path']);
            $categoryData["sqr:category{$i}"] = [
                'node' => end($path),
            ];
            $categoryData["sqr:categories"][] = $category['category_id'];
            $i++;
        }

        return $categoryData;
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
