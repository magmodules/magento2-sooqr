<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data;

use Exception;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CatalogPrice;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Service class for price data
 */
class Price
{

    public const REQUIRE = [
        'products',
        'grouped_price_type',
        'bundle_price_type'
    ];

    private CatalogPrice $commonPriceModel;
    private RuleFactory $resourceRuleFactory;
    private CatalogHelper $catalogHelper;
    private StoreManagerInterface $storeManager;
    private TimezoneInterface $localeDate;
    private CollectionFactory $collectionFactory;

    private $price = null;
    private $finalPrice = null;
    private $specialPrice = null;
    private $salesPrice = null;
    private $rulePrice = null;
    private $minPrice = null;
    private $maxPrice = null;
    private $totalPrice = null;
    private $websiteId = null;
    private $currecy = null;
    private $taxClasses = [];
    private $bundlePriceType = null;
    private $groupedPriceType = null;
    private $products = null;

    public function __construct(
        CatalogPrice $commonPriceModel,
        RuleFactory $resourceRuleFactory,
        CatalogHelper $catalogHelper,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        CollectionFactory $collectionFactory
    ) {
        $this->commonPriceModel = $commonPriceModel;
        $this->resourceRuleFactory = $resourceRuleFactory;
        $this->catalogHelper = $catalogHelper;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param array $productIds
     *
     * @param string $groupedPriceType options: min, max, total
     * @param string $bundlePriceType options: min, max, total
     * @param int $storeId
     * @return array
     */
    public function execute(
        array $productIds = [],
        string $groupedPriceType = '',
        string $bundlePriceType = '',
        int $storeId = 0
    ): array {
        $store = $this->getStore((int)$storeId);
        $this->currecy = $store->getDefaultCurrencyCode();
        $this->websiteId = $store->getWebsiteId();
        $this->taxClasses = [];

        $this->setData('products', $this->getProductData($productIds, $storeId));
        $this->setData('grouped_price_type', $groupedPriceType);
        $this->setData('bundle_price_type', $bundlePriceType);
        $rate = $store->getBaseCurrency()->getRate($this->currecy);

        foreach ($this->products as $product) {
            $this->setPrices($product, $this->groupedPriceType, $this->bundlePriceType);
            $percent = $this->getPercentage($product, $store);

            $result[$product->getId()] = [
                'price' => $this->round($percent * $this->price),
                'price_ex' => $this->round($this->price * $rate),
                'final_price' => $this->round($this->finalPrice ? $percent * $this->finalPrice : null),
                'final_price_ex' => $this->round($this->finalPrice * $rate),
                'sales_price' => $this->round($this->salesPrice ? $percent * $this->salesPrice : null),
                'min_price' => $this->round($this->minPrice ? $percent * $this->minPrice : null),
                'max_price' => $this->round($this->maxPrice ? $percent * $this->maxPrice : null),
                'special_price' => $this->round($this->specialPrice ? $percent * $this->specialPrice : null),
                'total_price' => $this->round($this->totalPrice ? $percent * $this->totalPrice : null),
                'sales_date_range' => $this->getSpecialPriceDateRang($product),
                'discount_perc' => $this->getDiscountPercentage(),
                'tax' => abs(1 - ($percent/$rate)) * 100,
                'currency' => $this->currecy
            ];
        }

        return $result ?? [];
    }

    /**
     * Calculate price multiplier including tax adjustments and currency rate differences.
     *
     * Returns a float multiplier based on the processed price (incl. tax) and original price,
     * potentially adjusted for base-to-store currency conversion.
     *
     * @param Product $product The product for which to calculate the price multiplier.
     * @param StoreInterface|null $store The store context used to resolve tax and currency.
     * @return float The price multiplier relative to base price.
     */
    private function getPercentage(Product $product, ?StoreInterface $store): float
    {
        $taxClassId = (int)$product->getTaxClassId();

        if (isset($this->taxClasses[$taxClassId])) {
            return $this->taxClasses[$taxClassId];
        }

        if ($this->price == 0.0) {
            $percent = 1.0;
        } else {
            $processed = $this->processPrice($product, (float)$this->price, $store);
            $percent = round($processed / $this->price, 2);
        }

        // Adjust for currency difference
        if ($store->getBaseCurrencyCode() !== $this->currecy) {
            $rate = $store->getBaseCurrency()->getRate($this->currecy);
            if ($rate > 0) {
                $percent *= $rate;
            }
        }

        $this->taxClasses[$taxClassId] = $percent;
        return $percent;
    }

    /**
     * @param int $storeId
     * @return StoreInterface|null
     */
    private function getStore(int $storeId = 0): ?StoreInterface
    {
        try {
            return $this->storeManager->getStore($storeId);
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @param string $type
     * @param mixed $data
     */
    public function setData($type, $data)
    {
        if (!$data) {
            return;
        }
        switch ($type) {
            case 'products':
                $this->products = $data;
                break;
            case 'grouped_price_type':
                $this->groupedPriceType = $data;
                break;
            case 'bundle_price_type':
                $this->bundlePriceType = $data;
                break;
        }
    }

    /**
     * @param array $productIds
     * @param int $storeId
     * @return Collection|AbstractDb
     */
    private function getProductData(array $productIds = [], int $storeId = 0)
    {
        $products = $this->collectionFactory->create()
            ->addFieldToSelect(['price', 'special_price', 'tax_class_id', 'special_from_date', 'special_to_date'])
            ->addFieldToFilter('entity_id', ['in' => $productIds])
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);

        // Join the price attributes
        $products->getSelect()->joinLeft(
            ['price_index' => $products->getTable('catalog_product_index_price')],
            join(
                ' AND ',
                [
                    'price_index.entity_id = e.entity_id',
                    'price_index.website_id = ' . $this->websiteId,
                    'price_index.customer_group_id = 0'
                ]
            ),
            ['final_price', 'min_price', 'max_price', 'price_index_price' => 'price']
        );

        return $products;
    }

    /**
     * @param Product $product
     * @param string|null $groupedPriceType
     * @param string|null $bundlePriceType
     */
    private function setPrices(Product $product, ?string $groupedPriceType, ?string $bundlePriceType): void
    {
        switch ($product->getTypeId()) {
            case 'configurable':
                $this->setConfigurablePrices($product);
                break;
            case 'grouped':
                $this->setGroupedPrices($product, $groupedPriceType);
                break;
            case 'bundle':
                $this->setBundlePrices($product, $bundlePriceType);
                break;
            default:
                $this->setSimplePrices($product);
                break;
        }

        $this->rulePrice = $this->getRulePrice($product);

        if ($this->finalPrice == '0.0000' && $this->minPrice > 0) {
            $this->finalPrice = $this->minPrice;
        }

        if ($this->finalPrice !== null && $this->finalPrice < $this->minPrice) {
            $this->minPrice = $this->finalPrice;
        }

        if ($this->finalPrice == null && $this->specialPrice !== null) {
            $this->finalPrice = $this->specialPrice;
        }

        if ($this->minPrice !== null && $this->price == null) {
            $this->price = $this->minPrice;
        }

        $this->salesPrice = null;
        if ($this->finalPrice !== null && ($this->price > $this->finalPrice)) {
            $this->salesPrice = $this->finalPrice;
        }

        if ($this->finalPrice === null && $this->price !== null) {
            $this->finalPrice = $this->price;
        }

        if ($this->price == '0.0000' && $this->finalPrice > 0) {
            $this->price = $this->finalPrice;
        }
    }

    private function round($price)
    {
        return round((float)$price, 2);
    }

    /**
     * @param Product $product
     */
    private function setConfigurablePrices(Product $product): void
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
        $this->specialPrice = $product->getData('special_price');
        $this->minPrice = $product['min_price'] >= 0 ? $product['min_price'] : null;
        $this->maxPrice = $product['max_price'] >= 0 ? $product['max_price'] : null;

        if ($this->minPrice && $this->minPrice < $this->finalPrice) {
            $this->finalPrice = $this->minPrice;
        }
    }

    /**
     * @param Product $product
     * @param string|null $groupedPriceType
     */
    private function setGroupedPrices(Product $product, ?string $groupedPriceType)
    {
        $minPrice = null;
        $maxPrice = null;
        $totalPrice = null;

        /* @var $typeInstance Grouped */
        $typeInstance = $product->getTypeInstance();
        $subProducts = $typeInstance->getAssociatedProducts($product);

        /** @var Product $subProduct */
        foreach ($subProducts as $subProduct) {
            $subProduct->setWebsiteId($this->websiteId);
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

        $this->minPrice = $product['min_price'] >= 0 ? $product['min_price'] : $minPrice;
        $this->maxPrice = $product['max_price'] >= 0 ? $product['max_price'] : $maxPrice;
        $this->totalPrice = $totalPrice;

        if ($groupedPriceType == 'max') {
            $this->price = $this->maxPrice;
            $this->finalPrice = $this->maxPrice;
            return;
        }

        if ($groupedPriceType == 'total') {
            $this->price = $totalPrice;
            $this->finalPrice = $totalPrice;
            return;
        }

        $this->price = $this->minPrice;
        $this->finalPrice = $this->minPrice;
    }

    /**
     * @param Product $product
     * @param string|null $bundlePriceType
     */
    private function setBundlePrices(Product $product, ?string $bundlePriceType): void
    {
        $this->setSimplePrices($product);

        if ($bundlePriceType == 'max') {
            $this->price = $this->maxPrice;
            $this->finalPrice = $this->maxPrice;
        }

        if ($bundlePriceType == 'min') {
            $this->price = $this->minPrice;
            $this->finalPrice = $this->minPrice;
        }
    }

    /**
     * @param Product $product
     */
    private function setSimplePrices(Product $product)
    {
        $this->price = $product->getData('price_index_price') != 0
            ? $product->getData('price_index_price')
            : $product->getData('price');

        $this->finalPrice = $product->getData('final_price') !== 0
            ? $product->getData('final_price')
            : null;

        $this->specialPrice = $product->getData('special_price')
            ? $product->getData('special_price')
            : 0;

        $this->minPrice = $product['min_price'] >= 0 ? $product['min_price'] : null;
        $this->maxPrice = $product['max_price'] >= 0 ? $product['max_price'] : null;
    }

    /**
     * Get special rule price from product
     *
     * @param Product $product
     *
     * @return float
     */
    private function getRulePrice(Product $product): float
    {
        try {
            $this->rulePrice = $this->resourceRuleFactory->create()->getRulePrice(
                $this->localeDate->scopeDate(),
                $this->websiteId,
                '',
                $product->getId()
            );
        } catch (Exception $exception) {
            return 0.0;
        }

        if ($this->rulePrice !== null && $this->rulePrice !== false) {
            $this->finalPrice = min($this->finalPrice, $this->rulePrice);
        }

        return (float)$this->rulePrice;
    }

    /**
     * Get product price with or without tax
     *
     * @param Product $product
     * @param float $price inputted product price
     * @param StoreInterface|null $store
     * @return float
     */
    private function processPrice(Product $product, float $price, ?StoreInterface $store): float
    {
        return (float)$this->catalogHelper->getTaxPrice(
            $product,
            $price,
            true,
            null,
            null,
            null,
            $store
        );
    }

    /**
     * Get product special price data range
     *
     * @param Product $product
     *
     * @return string
     */
    private function getSpecialPriceDateRang(Product $product): string
    {
        if ($this->specialPrice === null) {
            return '';
        }

        if ($this->specialPrice != $this->finalPrice) {
            return '';
        }

        if ($product->getSpecialFromDate() && $product->getSpecialToDate()) {
            $from = date('Y-m-d', strtotime($product->getSpecialFromDate()));
            $to = date('Y-m-d', strtotime($product->getSpecialToDate()));
            return $from . '/' . $to;
        }

        return '';
    }

    /**
     * Get product discount based on price and sales price
     *
     * @return string
     */
    private function getDiscountPercentage(): string
    {
        if ($this->price > 0 && $this->salesPrice > 0) {
            $discount = ($this->salesPrice - $this->price) / $this->price;
            $discount = $discount * -100;
            if ($discount > 0) {
                return round($discount, 1) . '%';
            }
        }
        return '0%';
    }

    /**
     * @return string[]
     */
    public function getRequiredParameters(): array
    {
        return self::REQUIRE;
    }

    /**
     * @param string $type
     */
    public function resetData(string $type = 'all')
    {
        if ($type == 'all') {
            unset($this->products);
            unset($this->groupedPriceType);
            unset($this->bundlePriceType);
        }
        switch ($type) {
            case 'products':
                unset($this->products);
                break;
            case 'grouped_price_type':
                unset($this->groupedPriceType);
                break;
            case 'bundle_price_type':
                unset($this->bundlePriceType);
                break;
        }
    }
}
