<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Service\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CatalogPrice;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

/**
 * Class PriceData
 *
 * @package Magmodules\Sooqr\Service\Product
 */
class PriceData
{

    private $price = null;
    private $finalPrice = null;
    private $specialPrice = null;
    private $salesPrice = null;
    private $rulePrice = null;
    private $minPrice = null;
    private $maxPrice = null;
    private $totalPrice = null;

    /**
     * @var CatalogPrice
     */
    private $commonPriceModel;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * PriceData constructor.
     *
     * @param CatalogPrice $commonPriceModel
     * @param RuleFactory $ruleFactory
     * @param CatalogHelper $catalogHelper
     */
    public function __construct(
        CatalogPrice $commonPriceModel,
        RuleFactory $ruleFactory,
        CatalogHelper $catalogHelper
    ) {
        $this->commonPriceModel = $commonPriceModel;
        $this->ruleFactory = $ruleFactory;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @param $config
     * @param Product $product
     *
     * @return array
     */
    public function getData($product, $config)
    {
        $this->setPrices($product, $config);

        return [
            'price' => $this->processPrice($product, $this->price, $config),
            'price_ex' => $this->processPrice($product, $this->price, $config, true),
            'final_price' => $this->processPrice($product, $this->finalPrice, $config),
            'final_price_ex' => $this->processPrice($product, $this->finalPrice, $config, true),
            'sales_price' => $this->processPrice($product, $this->salesPrice, $config),
            'min_price' => $this->processPrice($product, $this->minPrice, $config),
            'max_price' => $this->processPrice($product, $this->maxPrice, $config),
            'total_price' => $this->processPrice($product, $this->totalPrice, $config),
            'sales_date_range' => $this->getSpecialPriceDateRang($product),
            'discount_perc' => $this->getDiscountPercentage(),
        ];
    }

    /**
     * @param Product $product
     * @param array $config
     */
    private function setPrices($product, $config)
    {
        switch ($product->getTypeId()) {
            case 'configurable':
                $this->setConfigurablePrices($product);
                break;
            case 'grouped':
                $this->setGroupedPrices($product, $config);
                break;
            case 'bundle':
                $this->setBundlePrices($product, $config);
                break;
            default:
                $this->setSimplePrices($product);
                break;
        }

        $this->rulePrice = $this->getRulePrice($product, $config);

        if ($this->finalPrice !== null && $this->finalPrice < $this->minPrice) {
            $this->minPrice = $this->finalPrice;
        }

        if ($this->minPrice !== null && $this->price == null) {
            $this->price = $this->minPrice;
        }

        if ($this->finalPrice !== null && ($this->price > $this->finalPrice)) {
            $this->salesPrice = $this->finalPrice;
        }

        if ($this->finalPrice === null && $this->price !== null) {
            $this->finalPrice = $this->price;
        }
    }

    /**
     * @param Product $product
     */
    private function setConfigurablePrices($product)
    {
        /**
         * Check if config has a final_price (data catalog_product_index_price)
         * If final_price === null product is not salable (out of stock)
         */
        if ($product->getData('final_price') === null) {
            return;
        }

        $this->price = $product->getData('price');
        $this->finalPrice = $product->getData('final_price');
        $this->specialPrice = $product->getSpecialPrice();
        $this->minPrice = $product['min_price'] >= 0 ? $product['min_price'] : null;
        $this->maxPrice = $product['max_price'] >= 0 ? $product['max_price'] : null;
    }

    /**
     * @param Product $product
     * @param array $config
     */
    private function setGroupedPrices($product, $config)
    {
        $groupedPriceType = null;
        $minPrice = null;
        $maxPrice = null;
        $totalPrice = null;

        if (!empty($config['price_config']['grouped_price_type'])) {
            $groupedPriceType = $config['price_config']['grouped_price_type'];
        }

        /* @var $typeInstance Grouped */
        $typeInstance = $product->getTypeInstance();
        $subProducts = $typeInstance->getAssociatedProducts($product);

        /** @var Product $subProduct */
        foreach ($subProducts as $subProduct) {
            $subProduct->setWebsiteId($config['website_id']);
            if ($subProduct->isSalable()) {
                $price = $this->commonPriceModel->getCatalogPrice($subProduct);
                if ($price < $minPrice || $minPrice === null) {
                    $minPrice = $this->commonPriceModel->getCatalogPrice($subProduct);
                    $product->setTaxClassId($subProduct->getTaxClassId());
                }
                if ($price > $maxPrice || $maxPrice === null) {
                    $maxPrice = $this->commonPriceModel->getCatalogPrice($subProduct);
                    $product->setTaxClassId($subProduct->getTaxClassId());
                }
                if ($subProduct->getQty() > 0) {
                    $totalPrice += $price * $subProduct->getQty();
                } else {
                    $totalPrice += $price;
                }
            }
        }

        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
        $this->totalPrice = $totalPrice;

        if ($groupedPriceType == 'max') {
            $this->price = $maxPrice;
            $this->finalPrice = $maxPrice;

            return;
        }

        if ($groupedPriceType == 'total') {
            $this->price = $totalPrice;
            $this->finalPrice = $totalPrice;

            return;
        }

        $this->price = $minPrice;
        $this->finalPrice = $minPrice;
    }

    /**
     * @param Product $product
     * @param array $config
     */
    private function setBundlePrices($product, $config)
    {
        $this->price = $product->getPrice() !== 0 ? $product->getPrice() : null;
        $this->finalPrice = $product->getFinalPrice() !== 0 ? $product->getFinalPrice() : null;
        $this->specialPrice = $product->getSpecialPrice() !== 0 ? $product->getFinalPrice() : null;
        $this->minPrice = $product['min_price'] >= 0 ? $product['min_price'] : null;
        $this->maxPrice = $product['max_price'] >= 0 ? $product['max_price'] : null;

        $bundlePriceType = null;
        if (!empty($config['price_config']['bundle_price_type'])) {
            $bundlePriceType = $config['price_config']['bundle_price_type'];
        }

        if ($bundlePriceType == 'max') {
            $this->price = $this->maxPrice;
            $this->finalPrice = $this->maxPrice;
        }

        if ($bundlePriceType == 'min') {
            $this->price = $this->minPrice;
            $this->finalPrice = $this->minPrice;
        }

        return;
    }

    /**
     * @param Product $product
     */
    private function setSimplePrices($product)
    {
        $this->price = $product->getPrice() !== 0 ? $product->getPrice() : null;
        $this->finalPrice = $product->getFinalPrice() !== 0 ? $product->getFinalPrice() : null;
        $this->specialPrice = $product->getSpecialPrice() !== 0 ? $product->getFinalPrice() : null;
        $this->minPrice = $product['min_price'] >= 0 ? $product['min_price'] : null;
        $this->maxPrice = $product['max_price'] >= 0 ? $product['max_price'] : null;
    }

    /**
     * @param Product $product
     * @param array $config
     *
     * @return
     */
    private function getRulePrice($product, $config)
    {
        $this->rulePrice = $this->ruleFactory->create()->getRulePrice(
            $config['timestamp'],
            $config['website_id'],
            '',
            $product->getId()
        );

        if ($this->rulePrice !== null && $this->rulePrice !== false) {
            $this->finalPrice = min($this->finalPrice, $this->rulePrice);
        }

        return $this->rulePrice;
    }

    /**
     * @param Product $product
     * @param $price
     * @param array $config
     * @param bool $forceExVat
     *
     * @return string
     */
    private function processPrice($product, $price, $config, $forceExVat = false)
    {
        if (!empty($config['exchange_rate'])) {
            $price = $price * $config['exchange_rate'];
        }

        if ($forceExVat) {
            $price = $this->catalogHelper->getTaxPrice($product, $price, false);
        } else {
            $price = $this->catalogHelper->getTaxPrice($product, $price, true);
        }

        return $this->formatPrice($price, $config);
    }

    /**
     * @param float $price
     * @param array config
     *
     * @return string
     */
    private function formatPrice($price, $config)
    {
        $decimal = isset($config['decimal_point']) ? $config['decimal_point'] : '.';
        $price = number_format(floatval(str_replace(',', '.', $price)), 2, $decimal, '');
        if (!empty($config['use_currency']) && ($price >= 0)) {
            $price .= ' '.$config['currency'];
        }

        return $price;
    }

    /**
     * @param Product $product
     *
     * @return string|void
     */
    private function getSpecialPriceDateRang($product)
    {
        if ($this->specialPrice === null) {
            return;
        }

        if ($this->specialPrice != $this->finalPrice) {
            return;
        }

        if ($product->getSpecialFromDate() && $product->getSpecialToDate()) {
            $from = date('Y-m-d', strtotime($product->getSpecialFromDate()));
            $to = date('Y-m-d', strtotime($product->getSpecialToDate()));

            return $from.'/'.$to;
        }
    }

    /**
     * @return string
     */
    private function getDiscountPercentage()
    {
        if ($this->price > 0) {
            $discount = ($this->salesPrice - $this->price) / $this->price;
            $discount = $discount * -100;
            if ($discount > 0) {
                return round($discount, 1).'%';
            }
        }
    }
}
