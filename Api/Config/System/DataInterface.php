<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Config\System;

/**
 * Data Config group interface
 */
interface DataInterface extends SearchInterface
{

    /** General Group */
    public const XML_PATH_ENABLED = 'sooqr_data/general/enable';
    public const XML_PATH_FILENAME = 'sooqr_data/general/filename';

    /** Product Data Group */
    public const XML_PATH_NAME_SOURCE = 'sooqr_data/product_data/name_attribute';
    public const XML_PATH_SKU_SOURCE = 'sooqr_data/product_data/sku_attribute';
    public const XML_PATH_DESCRIPTION_SOURCE = 'sooqr_data/product_data/description_attribute';
    public const XML_PATH_BRAND_SOURCE = 'sooqr_data/product_data/brand_attribute';
    public const XML_PATH_IMAGE_SOURCE = 'sooqr_data/product_data/image_source';
    public const XML_PATH_IMAGE_RESIZE = 'sooqr_data/product_data/image_resize';
    public const XML_PATH_IMAGE_RESIZE_FIXED = 'sooqr_data/product_data/image_resize_fixed';
    public const XML_PATH_IMAGE_SIZE_CUSTOM = 'sooqr_data/product_data/image_size_custom';
    public const XML_PATH_EXTRA_FIELDS = 'sooqr_data/product_data/extra_fields';
    public const XML_PATH_ADD_RATING_SUMMARY = 'sooqr_data/product_data/add_rating_summary';
    public const CATEGORY_CHANGED_FLAG_PATH = 'sooqr_data/product_data/category_changed_flag';

    /** Product Types Group */
    public const XML_PATH_CONFIGURABLE = 'sooqr_data/product_types/configurable';
    public const XML_PATH_CONFIGURABLE_LINK = 'sooqr_data/product_types/configurable_link';
    public const XML_PATH_CONFIGURABLE_IMAGE = 'sooqr_data/product_types/configurable_image';
    public const XML_PATH_CONFIGURABLE_PARENT_ATTRIBUTES =
        'sooqr_data/product_types/configurable_use_parent_attributes';
    public const XML_PATH_CONFIGURABLE_NON_VISIBLE = 'sooqr_data/product_types/configurable_use_non_visible_fallback';
    public const XML_PATH_BUNDLE = 'sooqr_data/product_types/bundle_use';
    public const XML_PATH_BUNDLE_LINK = 'sooqr_data/product_types/bundle_link';
    public const XML_PATH_BUNDLE_IMAGE = 'sooqr_data/product_types/bundle_image';
    public const XML_PATH_BUNDLE_PARENT_ATTRIBUTES = 'sooqr_data/product_types/bundle_use_parent_attributes';
    public const XML_PATH_BUNDLE_NON_VISIBLE = 'sooqr_data/product_types/bundle_use_non_visible_fallback';
    public const XML_PATH_GROUPED = 'sooqr_data/product_types/grouped_use';
    public const XML_PATH_GROUPED_LINK = 'sooqr_data/product_types/grouped_link';
    public const XML_PATH_GROUPED_IMAGE = 'sooqr_data/product_types/grouped_image';
    public const XML_PATH_GROUPED_PARENT_PRICE = 'sooqr_data/product_types/grouped_price_logic';
    public const XML_PATH_GROUPED_PARENT_ATTRIBUTES = 'sooqr_data/product_types/grouped_use_parent_attributes';
    public const XML_PATH_GROUPED_NON_VISIBLE = 'sooqr_data/product_types/grouped_use_non_visible_fallback';

    /** Filter Options Group */
    public const XML_PATH_VISIBILITY = 'sooqr_data/product_filter/visibility_enable';
    public const XML_PATH_VISIBILITY_OPTIONS = 'sooqr_data/product_filter/visibility';
    public const XML_PATH_CATEGORY_FILTER = 'sooqr_data/product_filter/category_enable';
    public const XML_PATH_CATEGORY_FILTER_TYPE = 'sooqr_data/product_filter/category_type';
    public const XML_PATH_CATEGORY_IDS = 'sooqr_data/product_filter/category';
    public const XML_PATH_STOCK = 'sooqr_data/product_filter/stock';

    /** CMS Options Group */
    public const XML_PATH_CMS_ENABLE_TYPE = 'sooqr_data/cms/enable_type';
    public const XML_PATH_CMS_SELECTION = 'sooqr_data/cms/cms_selection';

    /**
     * Check if data synchronization is enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isDataEnabled(int $storeId = null): bool;

    /**
     * Get filename
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getFilename(int $storeId): string;

    /**
     * Return all enabled storeIds
     *
     * @return array
     */
    public function getAllEnabledStoreIds(): array;

    /**
     * Returns array of attributes
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getAttributes(int $storeId): array;

    /**
     * Get 'image' attribute
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getImageAttribute(int $storeId): string;

    /**
     * @return bool|mixed
     */
    public function getImageResize();

    /**
     * Returns array of static fields
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getStaticFields(int $storeId): array;

    /**
     * Get product data filters
     *
     * @param int $storeId
     * @return array
     */
    public function getFilters(int $storeId): array;

    /**
     * Get 'configurable' products data behaviour
     *
     * @param int $storeId
     * @return array
     */
    public function getConfigProductsBehaviour(int $storeId): array;

    /**
     * Get 'bundle' products data behaviour
     *
     * @param int $storeId
     * @return array
     */
    public function getBundleProductsBehaviour(int $storeId): array;

    /**
     * Get 'grouped' products data behaviour
     *
     * @param int $storeId
     * @return array
     */
    public function getGroupedProductsBehaviour(int $storeId): array;

    /**
     * Get enable type for CMS-pages
     *
     * @param int $storeId
     * @return int
     * @see \Magmodules\Sooqr\Model\Config\Source\Cms
     */
    public function getCmsEnableType(int $storeId): int;

    /**
     * Get CMS-selection
     *
     * @param int $storeId
     * @return string
     */
    public function getCmsSelection(int $storeId): string;

    /**
     * Get Category has changed flag
     *
     * @return bool
     */
    public function getCategoryChangedFlag(): bool;

    /**
     * Add rating summary
     *
     * @param int $storeId
     * @return bool
     */
    public function addRatingSummary(int $storeId): bool;

    /**
     * Set/unset a flag when category data is changed
     *
     * @param bool $value
     *
     * @return void
     */
    public function setCategoryChangedFlag(bool $value): void;
}
