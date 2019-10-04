<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Media\Config as CatalogProductMediaConfig;
use Magento\Catalog\Helper\Image as ProductImageHelper;
use Magento\Framework\Filter\FilterManager;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as GroupedResource;
use Magento\Bundle\Model\ResourceModel\Selection as BundleResource;
use Magmodules\Sooqr\Logger\GeneralLoggerInterface;
use Magmodules\Sooqr\Service\Product\InventoryData;
use Magmodules\Sooqr\Service\Product\PriceData;

/**
 * Class Product
 *
 * @package Magmodules\Sooqr\Helper
 */
class Product extends AbstractHelper
{

    /**
     * @var EavConfig
     */
    private $eavConfig;
    /**
     * @var FilterManager
     */
    private $filter;
    /**
     * @var ConfigurableResource
     */
    private $catalogProductTypeConfigurable;
    /**
     * @var GroupedResource
     */
    private $catalogProductTypeGrouped;
    /**
     * @var BundleResource
     */
    private $catalogProductTypeBundle;
    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSet;
    /**
     * @var ProductImageHelper
     */
    private $productImageHelper;
    /**
     * @var GalleryReadHandler
     */
    private $galleryReadHandler;
    /**
     * @var CatalogProductMediaConfig
     */
    private $catalogProductMediaConfig;
    /**
     * @var InventoryData
     */
    private $inventoryData;
    /**
     * @var PriceData
     */
    private $priceData;
    /**
     * @var GeneralLoggerInterface
     */
    private $logger;

    /**
     * Product constructor.
     *
     * @param Context                         $context
     * @param GalleryReadHandler              $galleryReadHandler
     * @param CatalogProductMediaConfig       $catalogProductMediaConfig
     * @param ProductImageHelper              $productImageHelper
     * @param EavConfig                       $eavConfig
     * @param FilterManager                   $filter
     * @param AttributeSetRepositoryInterface $attributeSet
     * @param GroupedResource                 $catalogProductTypeGrouped
     * @param BundleResource                  $catalogProductTypeBundle
     * @param ConfigurableResource            $catalogProductTypeConfigurable
     * @param InventoryData                   $inventoryData
     * @param PriceData                       $priceData
     * @param GeneralLoggerInterface          $logger
     */
    public function __construct(
        Context $context,
        GalleryReadHandler $galleryReadHandler,
        CatalogProductMediaConfig $catalogProductMediaConfig,
        ProductImageHelper $productImageHelper,
        EavConfig $eavConfig,
        FilterManager $filter,
        AttributeSetRepositoryInterface $attributeSet,
        GroupedResource $catalogProductTypeGrouped,
        BundleResource $catalogProductTypeBundle,
        ConfigurableResource $catalogProductTypeConfigurable,
        InventoryData $inventoryData,
        PriceData $priceData,
        GeneralLoggerInterface $logger
    ) {
        $this->galleryReadHandler = $galleryReadHandler;
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
        $this->productImageHelper = $productImageHelper;
        $this->eavConfig = $eavConfig;
        $this->filter = $filter;
        $this->attributeSet = $attributeSet;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->catalogProductTypeGrouped = $catalogProductTypeGrouped;
        $this->catalogProductTypeBundle = $catalogProductTypeBundle;
        $this->inventoryData = $inventoryData;
        $this->priceData = $priceData;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getDataRow($product, $parent, $config)
    {
        $dataRow = [];

        $product = $this->inventoryData->addDataToProduct($product, $config);

        if (!$this->validateProduct($product, $parent, $config)) {
            return $dataRow;
        }

        if ($this->checkBackorder($product, $config['inventory'])) {
            $product->setIsInStock(2);
        }

        foreach ($config['attributes'] as $type => $attribute) {
            $simple = null;
            $productData = $product;
            if ($parent) {
                $parentTypeId = $parent->getTypeId();
                if (isset($attribute['parent'][$parentTypeId])) {
                    if ($attribute['parent'][$parentTypeId] > 0) {
                        $productData = $parent;
                        $simple = $product;
                    }
                }
            }

            if (($attribute['parent']['simple'] == 2) && !$parent) {
                continue;
            }

            if (empty($attribute['label'])) {
                continue;
            }

            $value = null;

            if (!empty($attribute['source']) || ($type == 'image_link')) {
                $value = $this->getAttributeValue(
                    $type,
                    $attribute,
                    $config,
                    $productData,
                    $simple
                );
                if ($type == 'image_link' && is_array($value)) {
                    $value = isset($value[0]) ? $value[0] : null;
                }
            }

            if (!empty($attribute['multi']) && empty($attribute['conditional']) && empty($attribute['condition'])) {
                foreach ($attribute['multi'] as $multi) {
                    $value = $this->getAttributeValue(
                        $type,
                        $multi,
                        $config,
                        $productData,
                        $simple
                    );
                    if (!empty($value)) {
                        break;
                    }
                }
            }
            if (!empty($attribute['static'])) {
                $value = $attribute['static'];
            }

            if (!empty($attribute['config'])) {
                $value = $config[$attribute['config']];
            }

            if (!empty($attribute['condition'])) {
                $value = $this->getCondition(
                    $attribute['condition'],
                    $productData,
                    $attribute
                );
            }

            if (!empty($attribute['conditional'])) {
                $value = $this->getConditional($attribute, $productData);
            }

            if (!empty($attribute['collection'])) {
                if ($dataCollection = $this->getAttributeCollection($type, $config, $productData)) {
                    $dataRow = array_merge($dataRow, $dataCollection);
                    continue;
                }
            }

            $label = $attribute['label'];
            if (is_numeric($label)) {
                $label = 'Field_' . $label;
            }

            $dataRow[$label] = $value;
        }

        return $dataRow;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $parent
     * @param                                $config
     *
     * @return bool
     */
    public function validateProduct($product, $parent, $config)
    {
        $filters = $config['filters'];
        if (!empty($parent)) {
            if (!empty($filters['stock'])) {
                if ($parent->getIsInStock() == 0) {
                    return false;
                }
            }
        }

        if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
            if (empty($parent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $inv
     *
     * @return bool
     */
    public function checkBackorder($product, $inv)
    {

        if ($product->getUseConfigManageStock() && (!$inv['config_manage_stock'])) {
            return false;
        }

        if (!$product->getUseConfigManageStock() && !$product->getManageStock()) {
            return false;
        }

        if (!$product->getIsInStock()) {
            return false;
        }

        if ($product->getIsInStock() && ($product->getQty() > 0)) {
            return false;
        }

        if ($product->getUseConfigBackorders() && empty($inv['config_backorders'])) {
            return false;
        }

        if (!$product->getBackorders()) {
            return false;
        }

        return true;
    }

    /**
     * @param                                $type
     * @param                                $attribute
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $simple
     *
     * @return mixed|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getAttributeValue($type, $attribute, $config, $product, $simple)
    {
        if (!empty($attribute['source']) && ($attribute['source'] == 'attribute_set_name')) {
            $type = 'attribute_set_name';
        }

        switch ($type) {
            case 'link':
                $value = $this->getProductUrl($product, $simple, $config);
                break;
            case 'image_link':
                $value = $this->getImage($attribute, $config, $product, $simple);
                break;
            case 'attribute_set_name':
                $value = $this->getAttributeSetName($product);
                break;
            case 'manage_stock':
            case 'min_sale_qty':
            case 'qty_increments':
            case 'allow_backorder':
                $value = $this->getStockValue($type, $product, $config['inventory']);
                break;
            case 'availability':
                $value = $this->getAvailability($product);
                break;
            default:
                $value = $this->getValue($attribute, $product);
                break;
        }

        if (!empty($value)) {
            if (!empty($attribute['actions']) || !empty($attribute['max'])) {
                $value = $this->getFormat($value, $attribute);
            }
            if (!empty($attribute['suffix'])) {
                if (!empty($config[$attribute['suffix']])) {
                    $value .= $config[$attribute['suffix']];
                }
            }
        } else {
            if (!empty($attribute['default'])) {
                $value = $attribute['default'];
            }
        }
        return $value;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $simple
     * @param                                $config
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getProductUrl($product, $simple, $config)
    {
        $url = null;
        if ($requestPath = $product->getRequestPath()) {
            $url = $config['base_url'] . $requestPath;
        } else {
            $url = $config['base_url'] . 'catalog/product/view/id/' . $product->getEntityId();
        }
        if (!empty($config['utm_code'])) {
            if ($config['utm_code'][0] != '?') {
                $url .= '?' . $config['utm_code'];
            } else {
                $url .= $config['utm_code'];
            }
        }
        if (!empty($simple)) {
            if ($product->getTypeId() == 'configurable') {
                if (isset($config['filters']['link']['configurable'])) {
                    if ($config['filters']['link']['configurable'] == 2) {
                        $options = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                        foreach ($options as $option) {
                            if ($id = $simple->getResource()->getAttributeRawValue(
                                $simple->getId(),
                                $option['attribute_code'],
                                $config['store_id']
                            )
                            ) {
                                $urlExtra[] = $option['attribute_id'] . '=' . $id;
                            }
                        }
                    }
                }
            }
            if (!empty($urlExtra) && !empty($url)) {
                $url = $url . '#' . implode('&', $urlExtra);
            }
        }

        return $url;
    }

    /**
     * @param                                $attribute
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $simple
     *
     * @return string|array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getImage($attribute, $config, $product, $simple)
    {
        if ($simple != null) {
            $typeId = $product->getTypeId();
            if (in_array($product->getTypeId(), ['configurable', 'bundle', 'grouped'])) {
                if ($config['filters']['image'][$typeId] == 2) {
                    $imageSimple = $this->getImageData($attribute, $config, $simple);
                    if (!empty($imageSimple)) {
                        return $imageSimple;
                    }
                }

                if ($config['filters']['image'][$typeId] == 3) {
                    $imageSimple = $this->getImageData($attribute, $config, $simple);
                    $imageParent = $this->getImageData($attribute, $config, $product);

                    $images = [];
                    if (is_array($imageSimple)) {
                        $images = $imageSimple;
                    } else {
                        if (!empty($imageSimple)) {
                            $images[] = $imageSimple;
                        }
                    }
                    if (is_array($imageParent)) {
                        $images = array_merge($images, $imageParent);
                    } else {
                        if (!empty($imageParent)) {
                            $images[] = $imageParent;
                        }
                    }

                    return array_unique($images);
                }
                if ($config['filters']['image'][$typeId] == 4) {
                    $imageParent = $this->getImageData($attribute, $config, $product);
                    $imageSimple = $this->getImageData($attribute, $config, $simple);

                    $images = [];
                    if (is_array($imageParent)) {
                        $images = $imageParent;
                    } else {
                        if (!empty($imageParent)) {
                            $images[] = $imageParent;
                        }
                    }
                    if (is_array($imageSimple)) {
                        $images = array_merge($images, $imageSimple);
                    } else {
                        if (!empty($imageSimple)) {
                            $images[] = $imageSimple;
                        }
                    }

                    return array_unique($images);
                }
            }
        }

        return $this->getImageData($attribute, $config, $product);
    }

    /**
     * @param                                $attribute
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string|array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getImageData($attribute, $config, $product)
    {
        if (empty($attribute['source']) || ($attribute['source'] == 'all')) {
            $images = [];
            if (!empty($attribute['main']) && ($attribute['main'] != 'last')) {
                if ($url = $product->getData($attribute['main'])) {
                    if ($url != 'no_selection') {
                        $images[] = $this->catalogProductMediaConfig->getMediaUrl($url);
                    }
                }
            }
            $this->galleryReadHandler->execute($product);
            $galleryImages = $product->getMediaGallery('images');
            foreach ($galleryImages as $image) {
                if (empty($image['disabled']) || !empty($config['inc_hidden_image'])) {
                    $images[] = $this->catalogProductMediaConfig->getMediaUrl($image['file']);
                }
            }
            if (!empty($attribute['main']) && ($attribute['main'] == 'last')) {
                $imageCount = count($images);
                if ($imageCount > 1) {
                    $mainImage = $images[$imageCount - 1];
                    array_unshift($images, $mainImage);
                }
            }

            return array_unique($images);
        } else {
            $img = null;
            if (!empty($attribute['resize'])) {
                $source = $attribute['source'];
                $size = $attribute['resize'];
                return $this->getResizedImage($product, $source, $size);
            }
            if ($url = $product->getData($attribute['source'])) {
                if ($url != 'no_selection') {
                    $img = $this->catalogProductMediaConfig->getMediaUrl($url);
                }
            }

            if (empty($img)) {
                $source = $attribute['source'];
                if ($source == 'image') {
                    $source = 'small_image';
                }
                if ($url = $product->getData($source)) {
                    if ($url != 'no_selection') {
                        $img = $this->catalogProductMediaConfig->getMediaUrl($url);
                    }
                }
            }
            return $img;
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $source
     * @param                                $size
     *
     * @return string
     */
    public function getResizedImage($product, $source, $size)
    {
        $size = explode('x', $size);
        $width = $size[0];
        $height = end($size);

        $imageId = [
            'image'       => 'product_base_image',
            'thumbnail'   => 'product_thumbnail_image',
            'small_image' => 'product_small_image'
        ];

        if (isset($imageId[$source])) {
            $source = $imageId[$source];
        } else {
            $source = 'product_base_image';
        }

        $resizedImage = $this->productImageHelper->init($product, $source)
            ->constrainOnly(true)
            ->keepAspectRatio(true)
            ->keepTransparency(true)
            ->keepFrame(false);

        if ($height > 0 && $width > 0) {
            $resizedImage->resize($width, $height);
        } else {
            $resizedImage->resize($width);
        }

        return $resizedImage->getUrl();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getAttributeSetName($product)
    {
        try {
            $attributeSetRepository = $this->attributeSet->get($product->getAttributeSetId());
            return $attributeSetRepository->getAttributeSetName();
        } catch (\Exception $e) {
            $this->logger->add('getAttributeSetName', $e->getMessage());
        }

        return false;
    }

    /**
     * @param                                $attribute
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $inventory
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getStockValue($attribute, $product, $inventory)
    {
        if ($attribute == 'manage_stock') {
            if ($product->getData('use_config_manage_stock')) {
                return $inventory['config_manage_stock'];
            } else {
                return $product->getData('manage_stock');
            }
        }
        if ($attribute == 'min_sale_qty') {
            if ($product->getData('use_config_min_sale_qty')) {
                return $inventory['config_min_sale_qty'];
            } else {
                return $product->getData('min_sale_qty');
            }
        }
        if ($attribute == 'backorders') {
            if ($product->getData('use_config_backorders')) {
                return $inventory['use_config_backorders'];
            } else {
                return $product->getData('backorders');
            }
        }
        if ($attribute == 'qty_increments') {
            if ($product->getData('use_config_enable_qty_inc')) {
                if (!$inventory['config_enable_qty_inc']) {
                    return false;
                }
            } else {
                if (!$product->getData('enable_qty_inc')) {
                    return false;
                }
            }
            if ($product->getData('use_config_qty_increments')) {
                return $inventory['config_qty_increments'];
            } else {
                return $product->getData('qty_increments');
            }
        }

        return '';
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return int
     */
    public function getAvailability($product)
    {
        if ($product->getIsSalable() && $product->getIsInStock()) {
            return 1;
        }

        return 0;
    }

    /**
     * @param                                $attribute
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getValue($attribute, $product)
    {
        try {
            if ($attribute['type'] == 'media_image') {
                if ($url = $product->getData($attribute['source'])) {
                    return $this->catalogProductMediaConfig->getMediaUrl($url);
                }
            }
            if ($attribute['type'] == 'select') {
                if ($attr = $product->getResource()->getAttribute($attribute['source'])) {
                    $value = $product->getData($attribute['source']);
                    $data = $attr->getSource()->getOptionText($value);
                    if (!is_array($data)) {
                        return (string)$data;
                    }
                }
            }
            if ($attribute['type'] == 'multiselect') {
                if ($attr = $product->getResource()->getAttribute($attribute['source'])) {
                    $value_text = [];
                    $values = explode(',', $product->getData($attribute['source']));
                    foreach ($values as $value) {
                        $value_text[] = $attr->getSource()->getOptionText($value);
                    }
                    return implode('/', $value_text);
                }
            }
        } catch (\Exception $e) {
            $this->logger->add('getValue', $e->getMessage());
        }

        return $product->getData($attribute['source']);
    }

    /**
     * @param $value
     * @param $attribute
     *
     * @return mixed|string
     */
    public function getFormat($value, $attribute)
    {
        if (!empty($attribute['actions'])) {
            $breaks = [
                '<br>',
                '<br/>',
                '<br />',
                '</p>',
                '</h1>',
                '</h2>',
                '</h3>',
                '</h4>',
                '</h5>',
                '</h6>',
                '<hr>',
                '</hr>',
                '</li>'
            ];

            $actions = $attribute['actions'];
            if (in_array('striptags', $actions)) {
                $value = str_replace(["\r", "\n"], "", $value);
                $value = str_replace($breaks, " ", $value);
                $value = str_replace('  ', ' ', $value);
                $value = strip_tags($value);
            }
            if (in_array('number', $actions)) {
                if (is_numeric($value)) {
                    $value = number_format($value, 2);
                }
            }
            if (in_array('round', $actions)) {
                if (is_numeric($value)) {
                    $value = round($value);
                }
            }
            if (in_array('replacetags', $actions)) {
                $value = str_replace(["\r", "\n"], "", $value);
                $value = str_replace($breaks, '\n', $value);
                $value = str_replace('  ', ' ', $value);
                $value = strip_tags($value);
            }
            if (in_array('replacetagsn', $actions)) {
                $value = str_replace(["\r", "\n"], "", $value);
                $value = str_replace("<li>", "- ", $value);
                $value = str_replace($breaks, '\\' . '\n', $value);
                $value = str_replace('  ', ' ', $value);
                $value = strip_tags($value);
            }
        }
        if (!empty($attribute['max'])) {
            $value = $this->filter->truncate($value, ['length' => $attribute['max']]);
        }

        return rtrim($value);
    }

    /**
     * @param                                $conditions
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $attribute
     *
     * @return string
     */
    public function getCondition($conditions, $product, $attribute)
    {
        $data = null;
        $value = $product->getData($attribute['source']);
        if ($attribute['source'] == 'is_in_stock') {
            $value = $this->getAvailability($product);
        }

        foreach ($conditions as $condition) {
            $ex = explode(':', $condition);
            if ($ex['0'] == '*') {
                $data = str_replace($ex[0] . ':', '', $condition);
            }
            if ($value == $ex['0']) {
                $data = str_replace($ex[0] . ':', '', $condition);
            }
        }

        if (!empty($attribute['multi'])) {
            $attributes = $attribute['multi'];
            foreach ($attributes as $att) {
                $data = str_replace('{{' . $att['source'] . '}}', $this->getValue($att, $product), $data);
            }
        }

        return $data;
    }

    /**
     * @param $attribute
     * @param $product
     *
     * @return mixed|string
     */
    public function getConditional($attribute, $product)
    {
        $dataIf = null;
        $dataElse = null;
        $conditions = $attribute['conditional'];
        $attributes = $attribute['multi'];

        $values = [];
        foreach ($attributes as $att) {
            $values['{{' . $att['source'] . '}}'] = $this->getValue($att, $product);
        }

        foreach ($conditions as $condition) {
            $ex = explode(':', $condition);
            if ($ex['0'] == '*') {
                if (isset($ex['1'])) {
                    $dataElse = $ex['1'];
                }
            } else {
                if (!empty($values[$ex['0']]) && isset($ex['1'])) {
                    $dataIf = $ex['1'];
                }
            }
        }

        foreach ($values as $key => $value) {
            $dataIf = str_replace($key, $value, $dataIf);
            $dataElse = str_replace($key, $value, $dataElse);
        }

        if (!empty($attribute['actions'])) {
            $dataIf = $this->getFormat($dataIf, $attribute);
            $dataElse = $this->getFormat($dataElse, $attribute);
        }

        if (!empty($dataIf)) {
            return $dataIf;
        }

        return $dataElse;
    }

    /**
     * @param                                $type
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getAttributeCollection($type, $config, $product)
    {
        if ($type == 'price') {
            return $this->getPriceCollection($config, $product);
        }

        return [];
    }

    /**
     * @param                                $config
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getPriceCollection($config, $product)
    {
        $priceFields = $config['price_config'];
        $priceData = $this->priceData->getData($product, $config);

        return [
            $priceFields['price']          => $priceData['price'],
            $priceFields['price_ex']       => $priceData['price_ex'],
            $priceFields['final_price']    => $priceData['final_price'],
            $priceFields['final_price_ex'] => $priceData['final_price_ex'],
        ];
    }

    /**
     * @param        $attributes
     * @param array  $filters
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addAttributeData($attributes, $filters)
    {
        foreach ($attributes as $key => $value) {
            if (!empty($value['source'])) {
                try {
                    if (!empty($value['multi'])) {
                        $multipleSources = explode(',', $value['multi']);
                        $sourcesArray = [];
                        foreach ($multipleSources as $source) {
                            if (strlen($source)) {
                                $type = $this->eavConfig->getAttribute('catalog_product', $source)->getFrontendInput();
                                $sourcesArray[] = ['type' => $type, 'source' => $source];
                            }
                        }
                        if (!empty($sourcesArray)) {
                            $attributes[$key]['multi'] = $sourcesArray;
                            if ($attributes[$key]['source'] == 'multi' || $attributes[$key]['source'] == 'conditional') {
                                unset($attributes[$key]['source']);
                            }
                        } else {
                            unset($attributes[$key]);
                        }
                    }
                    if (!empty($value['source'])) {
                        $type = $this->eavConfig->getAttribute('catalog_product', $value['source'])->getFrontendInput();
                        $attributes[$key]['type'] = $type;
                    }
                } catch (\Exception $e) {
                    $this->logger->add('addAttributeData', $e->getMessage());
                    unset($attributes[$key]);
                }
            }

            if (isset($attributes[$key]['parent'])) {
                unset($attributes[$key]['parent']);
            }

            $attributes[$key]['parent']['simple'] = (!empty($value['parent']) ? $value['parent'] : 0);

            if (isset($attributes[$key])) {
                if (isset($filters['parent_attributes'])) {
                    foreach ($filters['parent_attributes'] as $k => $v) {
                        if (in_array($key, $v)) {
                            $attributes[$key]['parent'][$k] = 1;
                        } else {
                            $parent = (!empty($value['parent']) ? $value['parent'] : 0);
                            $attributes[$key]['parent'][$k] = $parent;
                        }
                    }
                }
            }
        }
        return $attributes;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $products
     * @param                                                         $config
     *
     * @return array
     */
    public function getParentsFromCollection($products, $config)
    {
        $ids = [];
        $filters = $config['filters'];
        if (!empty($filters['relations'])) {
            foreach ($products as $product) {
                if ($parentIds = $this->getParentId($product, $filters)) {
                    $ids[$product->getEntityId()] = $parentIds;
                }
            }
        }
        return $ids;
    }

    /**
     * Return Parent ID from Simple.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $filters
     *
     * @return bool|string
     */
    public function getParentId($product, $filters)
    {
        $productId = $product->getEntityId();
        $visibility = $product->getVisibility();
        $parentIds = [];

        if (!in_array($product->getTypeId(), ['simple', 'downloadable', 'virtual'])) {
            return false;
        }

        if (in_array('configurable', $filters['relations'])
            && (($visibility == Visibility::VISIBILITY_NOT_VISIBLE) || !in_array(
                'configurable',
                $filters['nonvisible']
            ))
        ) {
            $configurableIds = $this->catalogProductTypeConfigurable->getParentIdsByChild($productId);
            if (!empty($configurableIds)) {
                $parentIds = array_merge($parentIds, $configurableIds);
            }
        }

        if (in_array('grouped', $filters['relations'])
            && (($visibility == Visibility::VISIBILITY_NOT_VISIBLE) || !in_array('grouped', $filters['nonvisible']))
        ) {
            $typeId = GroupedResource::LINK_TYPE_GROUPED;
            $groupedIds = $this->catalogProductTypeGrouped->getParentIdsByChild($productId, $typeId);
            if (!empty($groupedIds)) {
                $parentIds = array_merge($parentIds, $groupedIds);
            }
        }

        if (in_array('bundle', $filters['relations'])
            && (($visibility == Visibility::VISIBILITY_NOT_VISIBLE) || !in_array('bundle', $filters['nonvisible']))
        ) {
            $bundleIds = $this->catalogProductTypeBundle->getParentIdsByChild($productId);
            if (!empty($bundleIds)) {
                $parentIds = array_merge($parentIds, $bundleIds);
            }
        }

        return array_unique($parentIds);
    }
}
