<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magmodules\Sooqr\Helper\Product as ProductHelper;
use Magmodules\Sooqr\Helper\Category as CategoryHelper;
use Magmodules\Sooqr\Helper\Feed as FeedHelper;
use Magmodules\Sooqr\Service\Product\InventorySource;

/**
 * Class Source
 *
 * @package Magmodules\Sooqr\Helper
 */
class Source extends AbstractHelper
{

    const LIMIT_PREVIEW = 100;

    const XPATH_NAME_SOURCE = 'magmodules_sooqr/data/name_attribute';
    const XPATH_SKU_SOURCE = 'magmodules_sooqr/data/sku_attribute';
    const XPATH_DESCRIPTION_SOURCE = 'magmodules_sooqr/data/description_attribute';
    const XPATH_BRAND_SOURCE = 'magmodules_sooqr/data/brand_attribute';
    const XPATH_EXTRA_FIELDS = 'magmodules_sooqr/data/extra_fields';
    const XPATH_IMAGE_SOURCE = 'magmodules_sooqr/data/image_source';
    const XPATH_IMAGE_RESIZE = 'magmodules_sooqr/data/image_resize';
    const XPATH_IMAGE_SIZE_FIXED = 'magmodules_sooqr/data/image_size_fixed';
    const XPATH_IMAGE_SIZE_CUSTOM = 'magmodules_sooqr/data/image_size_custom';
    const XPATH_LIMIT = 'magmodules_sooqr/generate/limit';
    const XPATH_CATEGORY = 'magmodules_sooqr/data/category';
    const XPATH_VISBILITY = 'magmodules_sooqr/filter/visbility_enable';
    const XPATH_VISIBILITY_OPTIONS = 'magmodules_sooqr/filter/visbility';
    const XPATH_CATEGORY_FILTER = 'magmodules_sooqr/filter/category_enable';
    const XPATH_CATEGORY_FILTER_TYPE = 'magmodules_sooqr/filter/category_type';
    const XPATH_CATEGORY_IDS = 'magmodules_sooqr/filter/category';
    const XPATH_STOCK = 'magmodules_sooqr/filter/stock';
    const XPATH_RELATIONS_ENABLED = 'magmodules_sooqr/data/relations';
    const XPATH_PARENT_ATTS = 'magmodules_sooqr/data/parent_atts';
    const XPATH_TAX = 'magmodules_sooqr/data/tax';
    const XPATH_ADVANCED = 'magmodules_sooqr/generate/advanced';
    const XPATH_PAGING = 'magmodules_sooqr/generate/paging';
    const XPATH_DEBUG_MEMORY = 'magmodules_sooqr/generate/debug_memory';
    const XPATH_FILTERS = 'magmodules_sooqr/filter/filters';
    const XPATH_FILTERS_DATA = 'magmodules_sooqr/filter/filters_data';
    const XPATH_CONFIGURABLE = 'magmodules_sooqr/types/configurable';
    const XPATH_CONFIGURABLE_LINK = 'magmodules_sooqr/types/configurable_link';
    const XPATH_CONFIGURABLE_IMAGE = 'magmodules_sooqr/types/configurable_image';
    const XPATH_CONFIGURABLE_PARENT_ATTS = 'magmodules_sooqr/types/configurable_parent_atts';
    const XPATH_CONFIGURABLE_NONVISIBLE = 'magmodules_sooqr/types/configurable_nonvisible';
    const XPATH_BUNDLE = 'magmodules_sooqr/types/bundle';
    const XPATH_BUNDLE_LINK = 'magmodules_sooqr/types/bundle_link';
    const XPATH_BUNDLE_IMAGE = 'magmodules_sooqr/types/bundle_image';
    const XPATH_BUNDLE_PARENT_ATTS = 'magmodules_sooqr/types/bundle_parent_atts';
    const XPATH_BUNDLE_NONVISIBLE = 'magmodules_sooqr/types/bundle_nonvisible';
    const XPATH_GROUPED = 'magmodules_sooqr/types/grouped';
    const XPATH_GROUPED_LINK = 'magmodules_sooqr/types/grouped_link';
    const XPATH_GROUPED_IMAGE = 'magmodules_sooqr/types/grouped_image';
    const XPATH_GROUPED_PARENT_PRICE = 'magmodules_sooqr/types/grouped_parent_price';
    const XPATH_GROUPED_PARENT_ATTS = 'magmodules_sooqr/types/grouped_parrent_atts';
    const XPATH_GROUPED_NONVISIBLE = 'magmodules_sooqr/types/grouped_nonvisible';

    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var Product
     */
    private $productHelper;
    /**
     * @var Category
     */
    private $categoryHelper;
    /**
     * @var Feed
     */
    private $feedHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var InventorySource
     */
    private $inventorySource;

    /**
     * Source constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param General               $generalHelper
     * @param Category              $categoryHelper
     * @param Product               $productHelper
     * @param Feed                  $feedHelper
     * @param InventorySource       $inventorySource
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        GeneralHelper $generalHelper,
        CategoryHelper $categoryHelper,
        ProductHelper $productHelper,
        FeedHelper $feedHelper,
        InventorySource $inventorySource
    ) {
        $this->generalHelper = $generalHelper;
        $this->productHelper = $productHelper;
        $this->categoryHelper = $categoryHelper;
        $this->feedHelper = $feedHelper;
        $this->storeManager = $storeManager;
        $this->inventorySource = $inventorySource;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @param $type
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfig($storeId, $type)
    {
        $config = [];
        $config['flat'] = false;
        $config['type'] = $type;
        $config['store_id'] = $storeId;
        $config['website_id'] = $this->storeManager->getStore()->getWebsiteId();
        $config['timestamp'] = $this->generalHelper->getLocaleDate($storeId);
        $config['date_time'] = $this->generalHelper->getDateTime();
        $config['filters'] = $this->getProductFilters($type);
        $config['attributes'] = $this->getAttributes($type, $config['filters']);
        $config['price_config'] = $this->getPriceConfig();
        $config['base_url'] = $this->storeManager->getStore()->getBaseUrl();
        $config['feed_locations'] = $this->feedHelper->getFeedLocation($storeId, $type);
        $config['debug_memory'] = $this->generalHelper->getStoreValue(self::XPATH_DEBUG_MEMORY);
        $config['default_category'] = $this->generalHelper->getStoreValue(self::XPATH_CATEGORY);
        $config['inventory'] = $this->getInventoryData();
        $config['currency'] = $config['price_config']['currency'];
        $config['categories'] = $this->categoryHelper->getCollection(
            $storeId,
            'sooqr_cat',
            $config['default_category']
        );

        return $config;
    }

    /**
     * @param $type
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getProductFilters($type)
    {
        $filters = [];
        $filters['type_id'] = ['simple', 'downloadable', 'virtual'];
        $filters['relations'] = [];
        $filters['exclude_parents'] = [];
        $filters['nonvisible'] = [];
        $filters['parent_attributes'] = [];
        $filters['image'] = [];
        $filters['link'] = [];

        $configurabale = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE);
        switch ($configurabale) {
            case "parent":
                array_push($filters['type_id'], 'configurable');
                break;
            case "simple":
                array_push($filters['relations'], 'configurable');
                array_push($filters['exclude_parents'], 'configurable');

                if ($attributes = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['configurable'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'configurable');
                }

                if ($link = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_LINK)) {
                    $filters['link']['configurable'] = $link;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'link');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['link'];
                    }
                }

                if ($image = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_IMAGE)) {
                    $filters['image']['configurable'] = $image;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'image_link');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['image_link'];
                    }
                }

                break;
            case "both":
                array_push($filters['type_id'], 'configurable');
                array_push($filters['relations'], 'configurable');

                if ($attributes = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['configurable'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'configurable');
                }

                if ($link = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_LINK)) {
                    $filters['link']['configurable'] = $link;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'link');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['link'];
                    }
                }

                if ($image = $this->generalHelper->getStoreValue(self::XPATH_CONFIGURABLE_IMAGE)) {
                    $filters['image']['configurable'] = $image;
                    if (isset($filters['parent_attributes']['configurable'])) {
                        array_push($filters['parent_attributes']['configurable'], 'image_url');
                    } else {
                        $filters['parent_attributes']['configurable'] = ['image_url'];
                    }
                }

                break;
        }

        $bundle = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE);
        switch ($bundle) {
            case "parent":
                array_push($filters['type_id'], 'bundle');
                break;
            case "simple":
                array_push($filters['relations'], 'bundle');
                array_push($filters['exclude_parents'], 'bundle');

                if ($attributes = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['bundle'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'bundle');
                }

                if ($link = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_LINK)) {
                    $filters['link']['bundle'] = $link;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['link'];
                    }
                }

                if ($image = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_IMAGE)) {
                    $filters['image']['bundle'] = $image;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'image_link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['image_link'];
                    }
                }

                break;
            case "both":
                array_push($filters['type_id'], 'bundle');
                array_push($filters['relations'], 'bundle');

                if ($attributes = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_PARENT_ATTS)) {
                    $filters['parent_attributes']['bundle'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'bundle');
                }

                if ($link = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_LINK)) {
                    $filters['link']['bundle'] = $link;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['link'];
                    }
                }

                if ($image = $this->generalHelper->getStoreValue(self::XPATH_BUNDLE_IMAGE)) {
                    $filters['image']['bundle'] = $image;
                    if (isset($filters['parent_attributes']['bundle'])) {
                        array_push($filters['parent_attributes']['bundle'], 'image_link');
                    } else {
                        $filters['parent_attributes']['bundle'] = ['image_link'];
                    }
                }

                break;
        }

        $grouped = $this->generalHelper->getStoreValue(self::XPATH_GROUPED);
        switch ($grouped) {
            case "parent":
                array_push($filters['type_id'], 'grouped');
                break;
            case "simple":
                array_push($filters['relations'], 'grouped');
                array_push($filters['exclude_parents'], 'grouped');

                if ($attributes = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_PARENT_ATTS)) {
                    $filters['parent_attributes']['grouped'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'grouped');
                }

                if ($link = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_LINK)) {
                    $filters['link']['grouped'] = $link;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['link'];
                    }
                }

                if ($image = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_IMAGE)) {
                    $filters['image']['grouped'] = $image;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'image_link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['image_link'];
                    }
                }

                break;
            case "both":
                array_push($filters['type_id'], 'grouped');
                array_push($filters['relations'], 'grouped');

                if ($attributes = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_PARENT_ATTS)) {
                    $filters['parent_attributes']['grouped'] = explode(',', $attributes);
                }

                if ($nonVisible = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_NONVISIBLE)) {
                    array_push($filters['nonvisible'], 'grouped');
                }

                if ($link = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_LINK)) {
                    $filters['link']['grouped'] = $link;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['link'];
                    }
                }

                if ($image = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_IMAGE)) {
                    $filters['image']['grouped'] = $image;
                    if (isset($filters['parent_attributes']['grouped'])) {
                        array_push($filters['parent_attributes']['grouped'], 'image_link');
                    } else {
                        $filters['parent_attributes']['grouped'] = ['image_link'];
                    }
                }

                break;
        }

        $visibilityFilter = $this->generalHelper->getStoreValue(self::XPATH_VISBILITY);
        if ($visibilityFilter) {
            $visibility = $this->generalHelper->getStoreValue(self::XPATH_VISIBILITY_OPTIONS);
            $filters['visibility'] = explode(',', $visibility);
            $filters['visibility_parents'] = $filters['visibility'];
        } else {
            $filters['visibility'] = [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ];
            $filters['visibility_parents'] = $filters['visibility'];
            if (!empty($filters['relations'])) {
                array_push($filters['visibility'], Visibility::VISIBILITY_NOT_VISIBLE);
            }
        }

        $filters['limit'] = '';
        if ($type == 'preview') {
            $filters['limit'] = self::LIMIT_PREVIEW;
        } else {
            $advanced = (int)$this->generalHelper->getStoreValue(self::XPATH_ADVANCED);
            $paging = preg_replace('/\D/', '', $this->generalHelper->getStoreValue(self::XPATH_PAGING));
            if ($advanced && ($paging > 0)) {
                $filters['limit'] = $paging;
            }
        }

        $filters['stock'] = $this->generalHelper->getStoreValue(self::XPATH_STOCK);

        $categoryFilter = $this->generalHelper->getStoreValue(self::XPATH_CATEGORY_FILTER);
        if ($categoryFilter) {
            $categoryIds = $this->generalHelper->getStoreValue(self::XPATH_CATEGORY_IDS);
            $filterType = $this->generalHelper->getStoreValue(self::XPATH_CATEGORY_FILTER_TYPE);
            if (!empty($categoryIds) && !empty($filterType)) {
                $filters['category_ids'] = explode(',', $categoryIds);
                $filters['category_type'] = $filterType;
            }
        }

        $filters['advanced'] = [];
        $productFilters = $this->generalHelper->getStoreValue(self::XPATH_FILTERS);
        if ($productFilters) {
            if ($advFilters = $this->generalHelper->getStoreValueArray(self::XPATH_FILTERS_DATA)) {
                foreach ($advFilters as $advFilter) {
                    array_push($filters['advanced'], $advFilter);
                }
            }
        }

        return $filters;
    }

    /**
     * @param $type
     * @param $filters
     *
     * @return array
     */
    public function getAttributes($type, $filters = [])
    {
        $attributes = [];
        $attributes['content_type'] = [
            'label'                     => 'sqr:content_type',
            'static'                    => 'product',
            'parent_selection_disabled' => 1
        ];
        $attributes['id'] = [
            'label'                     => 'sqr:id',
            'source'                    => 'entity_id',
            'max'                       => 50,
            'parent_selection_disabled' => 1
        ];
        $attributes['name'] = [
            'label'  => 'sqr:title',
            'source' => $this->generalHelper->getStoreValue(self::XPATH_NAME_SOURCE),
            'max'    => 200
        ];
        $attributes['sku'] = [
            'label'  => 'sqr:sku',
            'source' => $this->generalHelper->getStoreValue(self::XPATH_SKU_SOURCE),
            'max'    => 70
        ];
        $attributes['link'] = [
            'label'  => 'sqr:link',
            'source' => 'product_url',
            'max'    => 2000,
            'parent' => 1
        ];
        $attributes['description'] = [
            'label'   => 'sqr:description',
            'source'  => $this->generalHelper->getStoreValue(self::XPATH_DESCRIPTION_SOURCE),
            'max'     => 5000,
            'actions' => ['striptags']
        ];
        $attributes['image_link'] = [
            'label'  => 'sqr:image_link',
            'source' => $this->generalHelper->getStoreValue(self::XPATH_IMAGE_SOURCE),
            'resize' => $this->getImageResize(),
        ];
        $attributes['price'] = [
            'label'                     => 'sqr:price',
            'collection'                => 'price',
            'parent_selection_disabled' => 1
        ];
        $attributes['currency'] = [
            'label'                     => 'sqr:currency',
            'config'                    => 'currency',
            'parent_selection_disabled' => 1,
        ];
        $attributes['brand'] = [
            'label'  => 'sqr:brand',
            'source' => $this->generalHelper->getStoreValue(self::XPATH_BRAND_SOURCE),
            'max'    => 70
        ];
        $attributes['product_type'] = [
            'label'                     => 'sqr:product_object_type',
            'source'                    => 'type_id',
            'parent_selection_disabled' => 1,
        ];
        $attributes['status'] = [
            'label'                     => 'sqr:status',
            'source'                    => 'status',
            'parent_selection_disabled' => 1,
        ];
        $attributes['visibility'] = [
            'label'                     => 'sqr:visibility',
            'source'                    => 'visibility',
            'parent_selection_disabled' => 1,
        ];
        $attributes['assoc_id'] = [
            'label'                     => 'sqr:assoc_id',
            'source'                    => $attributes['id']['source'],
            'parent_selection_disabled' => 1,
            'parent'                    => 1
        ];
        $attributes['is_bundle'] = [
            'label'                     => 'sqr:is_bundle',
            'source'                    => 'type_id',
            'condition'                 => [
                '*:false',
                'bundle:true',
            ],
            'parent_selection_disabled' => 1,
        ];
        $attributes['is_parent'] = [
            'label'                     => 'sqr:is_parent',
            'source'                    => 'type_id',
            'condition'                 => [
                '*:false',
                'configurable:true',
            ],
            'parent_selection_disabled' => 1,
        ];
        $attributes['availability'] = [
            'label'                     => 'sqr:availability',
            'source'                    => 'is_in_stock',
            'parent_selection_disabled' => 1,
            'condition'                 => [
                '1:In Stock',
                '0:Out of Stock'
            ]
        ];
        if ($extraFields = $this->getExtraFields()) {
            $attributes = array_merge($attributes, $extraFields);
        }

        if ($type == 'parent') {
            return $attributes;
        } else {
            return $this->productHelper->addAttributeData($attributes, $filters);
        }
    }

    /**
     * @return bool|mixed
     */
    public function getImageResize()
    {

        $resize = $this->generalHelper->getStoreValue(self::XPATH_IMAGE_RESIZE);

        if ($resize == 'fixed') {
            return $this->generalHelper->getStoreValue(self::XPATH_IMAGE_SIZE_FIXED);
        }
        if ($resize == 'custom') {
            return $this->generalHelper->getStoreValue(self::XPATH_IMAGE_SIZE_CUSTOM);
        }

        return false;
    }

    /**
     * @return array
     */
    public function getExtraFields()
    {
        $extraFields = [];
        if ($attributes = $this->generalHelper->getStoreValueArray(self::XPATH_EXTRA_FIELDS)) {
            $i = 0;
            foreach ($attributes as $attribute) {
                $extraFields['extra_' . $i] = [
                    'label'  => 'sqr:' . strtolower($attribute['attribute']),
                    'source' => $attribute['attribute']
                ];
                $i++;
            }
        }

        return $extraFields;
    }

    /**
     * @return array
     */
    public function getPriceConfig()
    {
        $store = $this->storeManager->getStore();

        $priceFields = [];
        $priceFields['price'] = 'sqr:normal_price';
        $priceFields['final_price'] = 'sqr:price';
        $priceFields['currency'] = $store->getCurrentCurrency()->getCode();
        $priceFields['exchange_rate'] = $store->getBaseCurrency()->getRate($priceFields['currency']);
        $priceFields['grouped_price_type'] = $this->generalHelper->getStoreValue(self::XPATH_GROUPED_PARENT_PRICE);

        if ($this->generalHelper->getStoreValue(self::XPATH_TAX)) {
            $priceFields['incl_vat'] = true;
        }

        return $priceFields;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInventoryData()
    {
        $invAtt = [];
        $invAtt['attributes'][] = 'is_in_stock';

        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $invAtt['stock_id'] = $this->inventorySource->execute($websiteCode);

        return $invAtt;
    }

    /**
     * @param                                $dataRow
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $config
     *
     * @return string
     */
    public function reformatData($dataRow, $product, $config)
    {
        if ($categoryData = $this->getCategoryData($product, $config['categories'])) {
            $dataRow = array_merge($dataRow, $categoryData);
        }
        $xml = $this->getXmlFromArray($dataRow, 'item');

        return $xml;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $categories
     *
     * @return array
     */
    public function getCategoryData($product, $categories)
    {
        $data = [];
        $categoryData = [];
        foreach ($product->getCategoryIds() as $catId) {
            if (!empty($categories[$catId])) {
                $i = 0;
                $category = $categories[$catId];
                foreach ($category['path'] as $path) {
                    $categoryData[$i][] = $path;
                    $i++;
                }
            }
        }
        $i = 1;
        $p = 0;
        if (!empty($categoryData)) {
            foreach ($categoryData as $cat) {
                foreach (array_unique($cat) as $catName) {
                    $data['sqr:category' . $i]['node' . $p] = $catName;
                    $p++;
                }
                $i++;
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @param $type
     *
     * @return string
     */
    public function getXmlFromArray($data, $type)
    {
        $xml = '  <' . $type . '>' . PHP_EOL;
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= '   <' . $key . '>' . PHP_EOL;
                foreach ($value as $key2 => $value2) {
                    if (!empty($value2)) {
                        if (strpos($key2, 'node') !== false) {
                            $key2 = 'node';
                        }
                        $xml .= sprintf('      <%s>%s</%s>', $key2, htmlspecialchars($value2, ENT_XML1), $key2) . PHP_EOL;
                    }
                }
                $xml .= '   </' . $key . '>' . PHP_EOL;
            } else {
                if (!empty($value)) {
                    $xml .= sprintf('   <%s>%s</%s>', $key, htmlspecialchars($value, ENT_XML1), $key) . PHP_EOL;
                }
            }
        }
        $xml .= '  </' . $type . '>' . PHP_EOL;

        return $xml;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $type
     *
     * @return string
     */
    public function getProductDataXml($product, $type)
    {
        $productData = [];
        foreach ($product->getData() as $k => $v) {
            if (!is_array($v)) {
                $productData[$k] = $v;
            }
        }

        return $this->getXmlFromArray($productData, $type);
    }
}
