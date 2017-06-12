<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magmodules\Sooqr\Helper\Product as ProductHelper;
use Magmodules\Sooqr\Helper\Category as CategoryHelper;
use Magmodules\Sooqr\Helper\Feed as FeedHelper;

class Source extends AbstractHelper
{

    const XML_PATH_NAME_SOURCE = 'magmodules_sooqr/data/name_attribute';
    const XML_PATH_SKU_SOURCE = 'magmodules_sooqr/data/sku_attribute';
    const XML_PATH_DESCRIPTION_SOURCE = 'magmodules_sooqr/data/description_attribute';
    const XML_PATH_BRAND_SOURCE = 'magmodules_sooqr/data/brand_attribute';
    const XML_PATH_EXTRA_FIELDS = 'magmodules_sooqr/data/extra_fields';
    const XML_PATH_IMAGE_SOURCE = 'magmodules_sooqr/data/image_source';
    const XML_PATH_IMAGE_RESIZE = 'magmodules_sooqr/data/image_resize';
    const XML_PATH_IMAGE_SIZE_FIXED = 'magmodules_sooqr/data/image_size_fixed';
    const XML_PATH_IMAGE_SIZE_CUSTOM = 'magmodules_sooqr/data/image_size_custom';
    const XML_PATH_LIMIT = 'magmodules_sooqr/generate/limit';
    const XML_PATH_CATEGORY = 'magmodules_sooqr/data/category';
    const XML_PATH_VISBILITY = 'magmodules_sooqr/filter/visbility_enable';
    const XML_PATH_VISIBILITY_OPTIONS = 'magmodules_sooqr/filter/visbility';
    const XML_PATH_CATEGORY_FILTER = 'magmodules_sooqr/filter/category_enable';
    const XML_PATH_CATEGORY_FILTER_TYPE = 'magmodules_sooqr/filter/category_type';
    const XML_PATH_CATEGORY_IDS = 'magmodules_sooqr/filter/category';
    const XML_PATH_STOCK = 'magmodules_sooqr/filter/stock';
    const XML_PATH_RELATIONS_ENABLED = 'magmodules_sooqr/data/relations';
    const XML_PATH_PARENT_ATTS = 'magmodules_sooqr/data/parent_atts';

    private $generalHelper;
    private $productHelper;
    private $categoryHelper;
    private $feedHelper;
    private $storeManager;

    /**
     * Source constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param General               $generalHelper
     * @param Category              $categoryHelper
     * @param Product               $productHelper
     * @param Feed                  $feedHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        GeneralHelper $generalHelper,
        CategoryHelper $categoryHelper,
        ProductHelper $productHelper,
        FeedHelper $feedHelper
    ) {
        $this->generalHelper = $generalHelper;
        $this->productHelper = $productHelper;
        $this->categoryHelper = $categoryHelper;
        $this->feedHelper = $feedHelper;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @param $type
     *
     * @return array
     */
    public function getConfig($storeId, $type)
    {
        $config = [];
        $config['type'] = $type;
        $config['flat'] = false;
        $config['store_id'] = $storeId;
        $config['attributes'] = $this->getAttributes();
        $config['price_config'] = $this->getPriceConfig();
        $config['currency'] = $config['price_config']['currency'];
        $config['url_type_media'] = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $config['base_url'] = $this->storeManager->getStore()->getBaseUrl();
        $config['feed_locations'] = $this->feedHelper->getFeedLocation($storeId, $type);
        $config['filters'] = $this->getProductFilters($type);
        $config['default_category'] = $this->generalHelper->getStoreValue(self::XML_PATH_CATEGORY);
        $config['inventory'] = $this->getInventoryData();
        $config['categories'] = $this->categoryHelper->getCollection($storeId, 'sooqr_cat',
            $config['default_category']);

        return $config;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getAttributes($type = 'feed')
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
            'source' => $this->generalHelper->getStoreValue(self::XML_PATH_NAME_SOURCE),
            'max'    => 200
        ];
        $attributes['sku'] = [
            'label'  => 'sqr:sku',
            'source' => $this->generalHelper->getStoreValue(self::XML_PATH_SKU_SOURCE),
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
            'source'  => $this->generalHelper->getStoreValue(self::XML_PATH_DESCRIPTION_SOURCE),
            'max'     => 5000,
            'actions' => ['striptags']
        ];
        $attributes['image_link'] = [
            'label'  => 'sqr:image_link',
            'source' => $this->generalHelper->getStoreValue(self::XML_PATH_IMAGE_SOURCE),
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
            'source' => $this->generalHelper->getStoreValue(self::XML_PATH_BRAND_SOURCE),
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

        if ($type != 'feed') {
            return $attributes;
        } else {
            $parentAttributes = $this->getParentAttributes();
            return $this->productHelper->addAttributeData($attributes, $parentAttributes);
        }
    }

    /**
     * @return bool|mixed
     */
    public function getImageResize()
    {

        $resize = $this->generalHelper->getStoreValue(self::XML_PATH_IMAGE_RESIZE);

        if ($resize == 'fixed') {
            return $this->generalHelper->getStoreValue(self::XML_PATH_IMAGE_SIZE_FIXED);
        }
        if ($resize == 'custom') {
            return $this->generalHelper->getStoreValue(self::XML_PATH_IMAGE_SIZE_CUSTOM);
        }

        return false;
    }

    /**
     * @return array
     */
    public function getExtraFields()
    {
        $extraFields = [];
        if ($attributes = $this->generalHelper->getStoreValueArray(self::XML_PATH_EXTRA_FIELDS)) {
            foreach ($attributes as $attribute) {
                $extraFields[$attribute['attribute']] = [
                    'label'  => 'sqr:' . strtolower($attribute['attribute']),
                    'source' => $attribute['attribute']
                ];
            }
        }

        return $extraFields;
    }

    /**
     * @return array|mixed
     */
    public function getParentAttributes()
    {
        $enabled = $this->generalHelper->getStoreValue(self::XML_PATH_RELATIONS_ENABLED);
        if ($enabled) {
            if ($attributes = $this->generalHelper->getStoreValue(self::XML_PATH_PARENT_ATTS)) {
                $attributes = explode(',', $attributes);

                return $attributes;
            }
        }

        return [];
    }

    /**
     * @return array
     */
    public function getPriceConfig()
    {
        $priceFields = [];
        $priceFields['price'] = 'sqr:normal_price';
        $priceFields['final_price'] = 'sqr:price';
        $priceFields['currency'] = ' ' . $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $priceFields['hide_currency'] = true;

        return $priceFields;
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getProductFilters($type)
    {
        $filters = [];

        $visibilityFilter = $this->generalHelper->getStoreValue(self::XML_PATH_VISBILITY);
        if ($visibilityFilter) {
            $visibility = $this->generalHelper->getStoreValue(self::XML_PATH_VISIBILITY_OPTIONS);
            $filters['visibility'] = explode(',', $visibility);
        } else {
            $filters['visibility'] = [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ];
        }

        $relations = $this->generalHelper->getStoreValue(self::XML_PATH_RELATIONS_ENABLED);
        if ($relations) {
            $filters['relations'] = 1;
            if (!$visibilityFilter) {
                array_push($filters['visibility'], Visibility::VISIBILITY_NOT_VISIBLE);
            }
        } else {
            $filters['relations'] = 0;
        }

        if ($type == 'preview') {
            $filters['limit'] = '100';
        } else {
            $filters['limit'] = (int)$this->generalHelper->getStoreValue(self::XML_PATH_LIMIT);
        }

        $filters['stock'] = $this->generalHelper->getStoreValue(self::XML_PATH_STOCK);

        $categoryFilter = $this->generalHelper->getStoreValue(self::XML_PATH_CATEGORY_FILTER);
        if ($categoryFilter) {
            $categoryIds = $this->generalHelper->getStoreValue(self::XML_PATH_CATEGORY_IDS);
            $filterType = $this->generalHelper->getStoreValue(self::XML_PATH_CATEGORY_FILTER_TYPE);
            if (!empty($categoryIds) && !empty($filterType)) {
                $filters['category_ids'] = explode(',', $categoryIds);
                $filters['category_type'] = $filterType;
            }
        }

        return $filters;
    }

    /**
     * @return array
     */
    public function getInventoryData()
    {
        $invAtt = [];
        $invAtt['attributes'][] = 'is_in_stock';

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
                        $xml .= '      <' . $key2 . '>' . htmlspecialchars($value2) . '</' . $key2 . '>' . PHP_EOL;
                    }
                }
                $xml .= '   </' . $key . '>' . PHP_EOL;
            } else {
                if (!empty($value)) {
                    $xml .= '   <' . $key . '>' . htmlspecialchars($value) . '</' . $key . '>' . PHP_EOL;
                }
            }
        }
        $xml .= '  </' . $type . '>' . PHP_EOL;

        return $xml;
    }
}
