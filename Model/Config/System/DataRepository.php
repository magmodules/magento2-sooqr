<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config\System;

use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigInterface;
use Magmodules\Sooqr\Api\Config\System\DataInterface;

/**
 * Data config provider class
 */
class DataRepository extends SearchRepository implements DataInterface
{

    /**
     * @inheritDoc
     */
    public function getAllEnabledStoreIds(): array
    {
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isDataEnabled((int)$store->getId()) && $store->getIsActive()) {
                $storeIds[] = (int)$store->getId();
            }
        }

        return $storeIds;
    }

    /**
     * @inheritDoc
     */
    public function isDataEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_ENABLED, $storeId)
            && $this->isSetFlag(ConfigInterface::XML_PATH_EXTENSION_ENABLE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getFilename(int $storeId = null): string
    {
        $fileName = strtolower($this->getStoreValue(self::XML_PATH_FILENAME, $storeId));
        return str_replace('.xml', '', $fileName) . '-' . $storeId . '.xml';
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(int $storeId): array
    {
        $attributes = [
            'name' => $this->getStoreValue(self::XML_PATH_NAME_SOURCE, $storeId),
            'sku' => $this->getStoreValue(self::XML_PATH_SKU_SOURCE, $storeId),
            'description' => $this->getStoreValue(self::XML_PATH_DESCRIPTION_SOURCE, $storeId),
            'brand' => $this->getStoreValue(self::XML_PATH_BRAND_SOURCE, $storeId),
        ];

        return $attributes + $this->getExtraAttributes($storeId);
    }

    /**
     * @param int $storeId
     * @return array|null
     */
    private function getExtraAttributes(int $storeId): ?array
    {
        $extraAttributes = [];
        foreach ($this->getStoreValueArray(self::XML_PATH_EXTRA_FIELDS, $storeId) as $savedValues) {
            foreach ($savedValues as $v) {
                $extraAttributes[$v] = $v;
            }
        }
        return $extraAttributes;
    }

    /**
     * @inheritDoc
     */
    public function getImageAttribute(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_IMAGE_SOURCE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getImageResize()
    {

        $resize = $this->getStoreValue(self::XML_PATH_IMAGE_RESIZE);

        if ($resize == 'fixed') {
            return $this->getStoreValue(self::XML_PATH_IMAGE_RESIZE_FIXED);
        }
        if ($resize == 'custom') {
            return $this->getStoreValue(self::XML_PATH_IMAGE_SIZE_CUSTOM);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getStaticFields(int $storeId): array
    {
        return [
            'currency' => $this->getStore($storeId)->getCurrentCurrency()->getCode(),
            'content_type' => 'product',
            'is_bundle' => [
                'type_id == bundle' => 'true',
                'type_id != bundle' => 'false',
            ],

            'is_parent' => [
                'type_id default ' => 'false',
                'type_id == configurable' => 'true',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFilters(int $storeId): array
    {
        return [
            'filter_by_visibility' => $this->restrictProductFeedByVisibility($storeId),
            'visibility' => $this->productFeedVisibilityRestrictions($storeId),
            'restrict_by_category' => $this->restrictProductFeedByCategory($storeId),
            'category_restriction_behaviour' => $this->categoryRestrictionsFilterType($storeId),
            'category' => $this->getCategoryIds($storeId),
            'exclude_out_of_stock' => $this->excludeOutOfStock($storeId),
            'add_disabled_products' => false,
            'exclude_attribute' => null,
        ];
    }

    /**
     * Restrict by 'visibility'
     *
     * @param int $storeId
     *
     * @return bool
     */
    private function restrictProductFeedByVisibility(int $storeId): bool
    {
        return $this->isSetFlag(self::XML_PATH_VISIBILITY, $storeId);
    }

    /**
     * Only add products with these following Visibility
     *
     * @param int $storeId
     *
     * @return array
     */
    private function productFeedVisibilityRestrictions(int $storeId): array
    {
        return explode(',', $this->getStoreValue(self::XML_PATH_VISIBILITY_OPTIONS, $storeId));
    }

    /**
     * Restrict by 'category'
     *
     * @param int $storeId
     *
     * @return bool
     */
    private function restrictProductFeedByCategory(int $storeId): bool
    {
        return $this->isSetFlag(self::XML_PATH_CATEGORY_FILTER, $storeId);
    }

    /**
     * Get category restriction filter type
     *
     * @param int $storeId
     *
     * @return string
     * @see \Magmodules\Sooqr\Model\Config\Source\CategoryTypeList
     */
    private function categoryRestrictionsFilterType(int $storeId): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_CATEGORY_FILTER_TYPE, $storeId);
    }

    /**
     * Only add products that belong to these categories
     *
     * @param int $storeId
     *
     * @return array
     */
    private function getCategoryIds(int $storeId): array
    {
        $categoryIds = $this->getStoreValue(self::XML_PATH_CATEGORY_IDS, $storeId);
        return $categoryIds ? explode(',', $categoryIds) : [];
    }

    /**
     * Exclude of out of stock products
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function excludeOutOfStock(int $storeId): bool
    {
        return $this->isSetFlag(self::XML_PATH_STOCK, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getConfigProductsBehaviour(int $storeId): array
    {
        return [
            'use' => $this->configurableProductLogic($storeId),
            'use_parent_url' => $this->configurableProductUrl($storeId),
            'use_parent_images' => $this->configurableProductImage($storeId),
            'use_parent_attributes' => $this->configurableParentAttributes($storeId),
            'use_non_visible_fallback' => $this->configurableNonVisibleFallback($storeId)
        ];
    }

    /**
     * Logic for 'configurable' products
     *
     * @param int $storeId
     *
     * @return string
     * @see \Magmodules\Sooqr\Model\Config\Source\Configurable\Options
     */
    private function configurableProductLogic(int $storeId): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_CONFIGURABLE, $storeId);
    }

    /**
     * Logic for 'configurable' product links
     *
     * @param int $storeId
     *
     * @return string
     */
    private function configurableProductUrl(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_CONFIGURABLE_LINK, $storeId);
    }

    /**
     * Logic for 'configurable' product image
     *
     * @param int $storeId
     *
     * @return int
     * @see \Magmodules\Sooqr\Model\Config\Source\Configurable\Image
     */
    private function configurableProductImage(int $storeId): int
    {
        return (int)$this->getStoreValue(self::XML_PATH_CONFIGURABLE_IMAGE, $storeId);
    }

    /**
     * Attributes that should be forced to get data from parent 'configurable' product
     *
     * @param int $storeId
     *
     * @return array
     */
    private function configurableParentAttributes(int $storeId): array
    {
        $attributes = $this->getStoreValue(self::XML_PATH_CONFIGURABLE_PARENT_ATTRIBUTES, $storeId);
        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * Flag to only use fallback to parent 'configurable' attributes on non visible parents
     *
     * @param int $storeId
     *
     * @return bool
     */
    private function configurableNonVisibleFallback(int $storeId): bool
    {
        return $this->isSetFlag(self::XML_PATH_CONFIGURABLE_NON_VISIBLE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getBundleProductsBehaviour(int $storeId): array
    {
        return [
            'use' => $this->bundleProductLogic($storeId),
            'use_parent_url' => $this->bundleProductUrl($storeId),
            'use_parent_images' => $this->bundleProductImage($storeId),
            'use_parent_attributes' => $this->bundleParentAttributes($storeId),
            'use_non_visible_fallback' => $this->bundleNonVisibleFallback($storeId)
        ];
    }

    /**
     * Logic for 'bundle' products
     *
     * @param int $storeId
     *
     * @return string
     * @see \Magmodules\Sooqr\Model\Config\Source\Bundle\Options
     */
    private function bundleProductLogic(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_BUNDLE, $storeId);
    }

    /**
     * Logic for 'bundle' product links
     *
     * @param int $storeId
     *
     * @return string
     */
    private function bundleProductUrl(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_BUNDLE_LINK, $storeId);
    }

    /**
     * Logic for 'bundle' product image
     *
     * @param int $storeId
     *
     * @return int
     * @see \Magmodules\Sooqr\Model\Config\Source\Bundle\Image
     */
    private function bundleProductImage(int $storeId): int
    {
        return (int)$this->getStoreValue(self::XML_PATH_BUNDLE_IMAGE, $storeId);
    }

    /**
     * Attributes that should be forced to get data from parent 'bundle' product
     *
     * @param int $storeId
     *
     * @return array
     */
    private function bundleParentAttributes(int $storeId): array
    {
        $attributes = $this->getStoreValue(self::XML_PATH_BUNDLE_PARENT_ATTRIBUTES, $storeId);
        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * Flag to only use fallback to parent 'bundle' attributes on non-visible parents
     *
     * @param int $storeId
     *
     * @return bool
     */
    private function bundleNonVisibleFallback(int $storeId): bool
    {
        return $this->isSetFlag(self::XML_PATH_BUNDLE_NON_VISIBLE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getGroupedProductsBehaviour(int $storeId): array
    {
        return [
            'use' => $this->groupedProductLogic($storeId),
            'use_parent_url' => $this->groupedProductUrl($storeId),
            'use_parent_images' => $this->groupedProductImage($storeId),
            'use_parent_attributes' => $this->groupedParentAttributes($storeId),
            'use_non_visible_fallback' => $this->groupedNonVisibleFallback($storeId),
            'price_logic' => $this->groupedPriceLogic($storeId),
        ];
    }

    /**
     * Logic for 'grouped' products
     *
     * @param int $storeId
     *
     * @return string
     * @see \Magmodules\Sooqr\Model\Config\Source\Grouped\Options
     */
    public function groupedProductLogic(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_GROUPED, $storeId);
    }

    /**
     * Logic for 'grouped' product links
     *
     * @param int $storeId
     *
     * @return string
     */
    public function groupedProductUrl(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_GROUPED_LINK, $storeId);
    }

    /**
     * Logic for 'grouped' product image
     *
     * @param int $storeId
     *
     * @return int
     * @see \Magmodules\Sooqr\Model\Config\Source\Grouped\Image
     */
    public function groupedProductImage(int $storeId): int
    {
        return (int)$this->getStoreValue(self::XML_PATH_GROUPED_IMAGE, $storeId);
    }

    /**
     * Attributes that should be forced to get data from parent 'grouped' product
     *
     * @param int $storeId
     *
     * @return array
     */
    public function groupedParentAttributes(int $storeId): array
    {
        $attributes = $this->getStoreValue(self::XML_PATH_GROUPED_PARENT_ATTRIBUTES, $storeId);
        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * Flag to only use fallback to parent 'grouped' attributes on non-visible parents
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function groupedNonVisibleFallback(int $storeId): bool
    {
        return $this->isSetFlag(self::XML_PATH_GROUPED_NON_VISIBLE, $storeId);
    }

    /**
     * Get grouped price logics
     *
     * @param int $storeId
     *
     * @return string
     */
    private function groupedPriceLogic(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_GROUPED_PARENT_PRICE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getCmsEnableType(int $storeId): int
    {
        return (int)$this->getStoreValue(self::XML_PATH_CMS_ENABLE_TYPE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getCmsSelection(int $storeId): string
    {
        return $this->getStoreValue(self::XML_PATH_CMS_SELECTION, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function addRatingSummary(int $storeId): bool
    {
        return $this->isSetFlag(self::XML_PATH_ADD_RATING_SUMMARY, $storeId);
    }
}
