<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Update Configuration paths
 */
class UpdateConfigPaths implements DataPatchInterface
{

    public const PATH_UPDATES = [
        'magmodules_sooqr/general/enable' => 'sooqr_general/general/enable',
        'magmodules_sooqr/implementation/account_id' => 'sooqr_general/credentials/account_id',
        'magmodules_sooqr/generate/enable' => 'sooqr_data/general/enable',
        'magmodules_sooqr/general/filename' => 'sooqr_data/general/filename',
        'magmodules_sooqr/generate/cron_frequency' => 'sooqr_data/general/cron_frequency',
        'magmodules_sooqr/cms/enable' => 'sooqr_data/cms/enable_type',
        'magmodules_sooqr/cms/cms_selection' => 'sooqr_data/cms/cms_selection',
        'magmodules_sooqr/data/name_attribute' => 'sooqr_data/product_data/name_attribute',
        'magmodules_sooqr/data/sku_attribute' => 'sooqr_data/product_data/sku_attribute',
        'magmodules_sooqr/data/description_attribute' => 'sooqr_data/product_data/description_attribute',
        'magmodules_sooqr/data/brand_attribute' => 'sooqr_data/product_data/brand_attribute',
        'magmodules_sooqr/data/extra_fields' => 'sooqr_data/product_data/extra_fields',
        'magmodules_sooqr/data/reviews' => 'sooqr_data/product_data/add_rating_summary',
        'magmodules_sooqr/data/image_source' => 'sooqr_data/product_data/image_source',
        'magmodules_sooqr/data/image_resize' => 'sooqr_data/product_data/image_resize',
        'magmodules_sooqr/data/image_size_fixed' => 'sooqr_data/product_data/image_resize_fixed',
        'magmodules_sooqr/data/image_size_custom' => 'sooqr_data/product_data/image_size_custom',
        'magmodules_sooqr/filter/visbility_enable' => 'sooqr_data/product_filter/visibility_enable',
        'magmodules_sooqr/filter/visbility' => 'sooqr_data/product_filter/visibility',
        'magmodules_sooqr/filter/category_enable' => 'sooqr_data/product_filter/category_enable',
        'magmodules_sooqr/filter/category_type' => 'sooqr_data/product_filter/category_type',
        'magmodules_sooqr/filter/category' => 'sooqr_data/product_filter/category',
        'magmodules_sooqr/filter/stock' => 'sooqr_data/product_filter/stock',
        'magmodules_sooqr/filter/filters' => 'sooqr_data/product_filter/filters',
        'magmodules_sooqr/filter/filters_data' => 'sooqr_data/product_filter/filters_data',
        'magmodules_sooqr/types/configurable' => 'sooqr_data/product_types/configurable_use',
        'magmodules_sooqr/types/configurable_link' => 'sooqr_data/product_types/configurable_link',
        'magmodules_sooqr/types/configurable_image' => 'sooqr_data/product_types/configurable_image',
        'magmodules_sooqr/types/configurable_parent_atts'
        => 'sooqr_data/product_types/configurable_use_parent_attributes',
        'magmodules_sooqr/types/configurable_nonvisible'
        => 'sooqr_data/product_types/configurable_use_non_visible_fallback',
        'magmodules_sooqr/types/bundle' => 'sooqr_data/product_types/bundle_use',
        'magmodules_sooqr/types/bundle_link' => 'sooqr_data/product_types/bundle_link',
        'magmodules_sooqr/types/bundle_image' => 'sooqr_data/product_types/bundle_image',
        'magmodules_sooqr/types/bundle_parent_atts' => 'sooqr_data/product_types/bundle_use_parent_attributes',
        'magmodules_sooqr/types/bundle_nonvisible' => 'sooqr_data/product_types/bundle_use_non_visible_fallback',
        'magmodules_sooqr/types/grouped' => 'sooqr_data/product_types/grouped_use',
        'magmodules_sooqr/types/grouped_link' => 'sooqr_data/product_types/grouped_link',
        'magmodules_sooqr/types/grouped_image' => 'sooqr_data/product_types/grouped_image',
        'magmodules_sooqr/types/grouped_price_logic' => 'sooqr_data/product_types/grouped_price_logic',
        'magmodules_sooqr/types/grouped_parent_atts' => 'sooqr_data/product_types/grouped_use_parent_attributes',
        'magmodules_sooqr/types/grouped_nonvisible' => 'sooqr_data/product_types/grouped_use_non_visible_fallback',
        'magmodules_sooqr/implementation/enable' => 'sooqr_search/frontend/enable',
        'magmodules_sooqr/implementation/loader' => 'sooqr_search/frontend/loader',
        'magmodules_sooqr/implementation/statistics' => 'sooqr_search/frontend/statistics',
        'magmodules_sooqr/implementation/advanced_parent' => 'sooqr_search/frontend/advanced_parent',
        'magmodules_sooqr/implementation/advanced_staging' => 'sooqr_search/frontend/advanced_staging',
        'magmodules_sooqr/implementation/advanced_debug' => 'sooqr_search/frontend/advanced_debug',
        'magmodules_sooqr/implementation/advanced_version' => 'sooqr_search/frontend/advanced_version',
        'magmodules_sooqr/implementation/advanced_custom_js' => 'sooqr_search/frontend/advanced_custom_js',
        'magmodules_sooqr/implementation/add_to_cart_controller' => 'sooqr_search/frontend/add_to_cart',
        'magmodules_sooqr/implementation/add_to_cart_ajax' => 'sooqr_search/frontend/add_to_cart_ajax',
        'magmodules_sooqr/implementation/add_to_cart_wishlist' => 'sooqr_search/frontend/add_to_wishlist',
    ];

    /**
     * @var CollectionFactory
     */
    private $configReaderFactory;
    /**
     * @var Config
     */
    private $configResource;

    /**
     * @param CollectionFactory $configReaderFactory
     * @param Config $configResource
     */
    public function __construct(
        CollectionFactory $configReaderFactory,
        Config $configResource
    ) {
        $this->configReaderFactory = $configReaderFactory;
        $this->configResource = $configResource;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        foreach (self::PATH_UPDATES as $pathOld => $pathNew) {
            $collection = $this->configReaderFactory->create()->addFieldToFilter('path', [
                'eq' => $pathOld,
            ]);
            foreach ($collection as $configItem) {
                $value = (string)$configItem->getData('value');
                if (!preg_match('/^\*+$/', $value) && !empty($value)) {
                    $this->configResource->saveConfig(
                        $pathNew,
                        $value,
                        $configItem->getData('scope'),
                        $configItem->getData('scope_id')
                    );
                }
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
