<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Flat\StateFactory;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magmodules\Sooqr\Helper\Product as ProductHelper;

class Products
{

    private $productCollectionFactory;
    private $productAttributeCollectionFactory;
    private $productFlatState;
    private $stockHelper;
    private $productHelper;

    /**
     * Products constructor.
     *
     * @param ProductCollectionFactory          $productCollectionFactory
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     * @param StockHelper                       $stockHelper
     * @param StateFactory                      $productFlatState
     * @param ProductHelper                     $productHelper
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        StockHelper $stockHelper,
        StateFactory $productFlatState,
        ProductHelper $productHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->productFlatState = $productFlatState;
        $this->stockHelper = $stockHelper;
        $this->productHelper = $productHelper;
    }

    /**
     * @param        $config
     * @param        $productIds
     *
     * @return $this|int
     */
    public function getCollection($config, $productIds)
    {
        $flat = $config['flat'];
        $filters = $config['filters'];
        $attributes = $this->getAttributes($config['attributes']);

        if (!$flat) {
            $productFlatState = $this->productFlatState->create(['isAvailable' => false]);
        } else {
            $productFlatState = $this->productFlatState->create(['isAvailable' => true]);
        }

        $collection = $this->productCollectionFactory
            ->create(['catalogProductFlatState' => $productFlatState])
            ->addAttributeToSelect($attributes)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addMinimalPrice()
            ->addUrlRewrite()
            ->addFinalPrice();

        if (!empty($filters['visibility'])) {
            $collection->addAttributeToFilter('visibility', ['in' => $filters['visibility']]);
        }

        if (!empty($filters['stock'])) {
            $this->stockHelper->addInStockFilterToCollection($collection);
        }

        if (!empty($productIds)) {
            $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
        }

        if (!empty($filters['category_ids'])) {
            if (!empty($filters['category_type'])) {
                $collection->addCategoriesFilter([$filters['category_type'] => $filters['category_ids']]);
            }
        }

        if (!empty($config['inventory']['attributes'])) {
            $collection->joinTable(
                'cataloginventory_stock_item',
                'product_id=entity_id',
                $config['inventory']['attributes']
            );
        }

        if (isset($filters['page_size']) && ($filters['page_size'] > 0)) {
            $collection->setPageSize($filters['page_size'])->getCurPage();
        }

        $collection->getSelect()->group('e.entity_id');

        return $collection;
    }

    /**
     * @param $selectedAttrs
     *
     * @return array
     */
    public function getAttributes($selectedAttrs)
    {
        $attributes = $this->getProductAttributes();
        foreach ($selectedAttrs as $selectedAtt) {
            if (!empty($selectedAtt['source'])) {
                if (empty($selectedAtt['inventory'])) {
                    $attributes[] = $selectedAtt['source'];
                }
            }
        }

        return array_unique($attributes);
    }

    /**
     * @return array
     */
    public function getProductAttributes()
    {
        return [
            'entity_id',
            'image',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'status',
            'tax_class_id',
            'weight',
            'product_has_weight'
        ];
    }

    /**
     * @param $products
     * @param $config
     *
     * @return $this|array
     */
    public function getParents($products, $config)
    {
        if (!empty($config['filters']['relations'])) {
            $ids = [];
            foreach ($products as $product) {
                if ($parentId = $this->productHelper->getParentId($product->getEntityId())) {
                    $ids[] = $parentId;
                }
            }

            if (empty($ids)) {
                return [];
            }

            $flat = false;

            if (!$flat) {
                $productFlatState = $this->productFlatState->create(['isAvailable' => false]);
            } else {
                $productFlatState = $this->productFlatState->create(['isAvailable' => true]);
            }

            $attributes = $this->getAttributes($config['attributes']);

            $collection = $this->productCollectionFactory
                ->create(['catalogProductFlatState' => $productFlatState])
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('entity_id', ['in' => $ids])
                ->addAttributeToSelect($attributes)
                ->addMinimalPrice()
                ->addUrlRewrite();

            return $collection->load();
        }

        return [];
    }
}
