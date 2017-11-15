<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model;

use Magmodules\Sooqr\Model\Collection\Products as ProductModel;
use Magmodules\Sooqr\Helper\Source as SourceHelper;
use Magmodules\Sooqr\Helper\Product as ProductHelper;
use Magmodules\Sooqr\Helper\Cms as CmsHelper;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magmodules\Sooqr\Helper\Feed as FeedHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

/**
 * Class Feed
 *
 * @package Magmodules\Sooqr\Model
 */
class Feed
{

    const XPATH_FEED_RESULT = 'magmodules_sooqr/feeds/results';
    const XPATH_GENERATE = 'magmodules_sooqr/generate/enable';
    /**
     * @var ProductModel
     */
    private $productModel;
    /**
     * @var SourceHelper
     */
    private $sourceHelper;
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var FeedHelper
     */
    private $feedHelper;
    /**
     * @var CmsHelper
     */
    private $cmsHelper;

    /**
     * Generate constructor.
     *
     * @param ProductModel  $productModel
     * @param SourceHelper  $sourceHelper
     * @param ProductHelper $productHelper
     * @param GeneralHelper $generalHelper
     * @param FeedHelper    $feedHelper
     * @param Emulation     $appEmulation
     * @param CmsHelper     $cmsHelper
     */
    public function __construct(
        ProductModel $productModel,
        SourceHelper $sourceHelper,
        ProductHelper $productHelper,
        GeneralHelper $generalHelper,
        FeedHelper $feedHelper,
        CmsHelper $cmsHelper,
        Emulation $appEmulation
    ) {
        $this->productModel = $productModel;
        $this->sourceHelper = $sourceHelper;
        $this->productHelper = $productHelper;
        $this->cmsHelper = $cmsHelper;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->appEmulation = $appEmulation;
    }

    /**
     * Generate all feeds
     */
    public function generateAll()
    {
        $storeIds = $this->generalHelper->getEnabledArray(self::XPATH_GENERATE);
        foreach ($storeIds as $storeId) {
            $this->generateByStore($storeId, 'cron');
        }
    }

    /**
     * @param        $storeId
     * @param string $type
     * @param array  $productIds
     * @param int    $page
     * @param bool   $data
     *
     * @return array
     */
    public function generateByStore($storeId, $type = 'manual', $productIds = [], $page = 1, $data = false)
    {
        $processed = 0;
        $pages = 1;

        $timeStart = microtime(true);
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->sourceHelper->getConfig($storeId, $type);
        $header = $this->feedHelper->getFeedHeader();
        $header = $this->sourceHelper->getXmlFromArray($header, 'config');
        $this->feedHelper->createFeed($config, $header);

        $productCollection = $this->productModel->getCollection($config, $page, $productIds);
        $size = $this->productModel->getCollectionCountWithFilters($productCollection);

        if (($config['filters']['limit'] > 0) && empty($productId)) {
            $pages = ceil($size / $config['filters']['limit']);
        }

        if ($type == 'preview') {
            $pages = $page;
        }

        do {
            $productCollection->setCurPage($page);
            $productCollection->load();

            $parentRelations = $this->productHelper->getParentsFromCollection($productCollection, $config);
            $parents = $this->productModel->getParents($parentRelations, $config);
            $processed += $this->getFeedData($productCollection, $parents, $config, $parentRelations, $data);

            if ($config['debug_memory']) {
                $this->feedHelper->addLog($page, $pages, $storeId);
            }

            $productCollection->clear();
            $parents = null;
            $page++;
        } while ($page <= $pages);

        if ($cmsPages = $this->cmsHelper->getCmsPages()) {
            foreach ($cmsPages as $item) {
                $row = $this->sourceHelper->getXmlFromArray($item, 'item');
                $this->feedHelper->writeRow($row);
            }
        }

        $pageSize = isset($config['filters']['limit']) ? $config['filters']['limit'] : '';
        $results = $this->feedHelper->getFeedResults($timeStart, $processed, $pageSize);
        $footer = $this->sourceHelper->getXmlFromArray($results, 'results');

        $this->feedHelper->writeFooter($footer);
        $this->feedHelper->updateResult($storeId,
            $processed,
            $results['processing_time'],
            $results['date_created'],
            $type,
            $page
        );

        $this->appEmulation->stopEnvironmentEmulation();

        return [
            'status' => 'success',
            'qty'    => $processed,
            'path'   => $config['feed_locations']['full_path'],
            'url'    => $config['feed_locations']['url'],
            'time'   => $results['processing_time']
        ];
    }

    /**
     * @param  \Magento\Catalog\Model\ResourceModel\Product\Collection $products
     * @param  \Magento\Catalog\Model\ResourceModel\Product\Collection $parents
     * @param                                                          $config
     * @param                                                          $parentRelations
     * @param bool                                                     $data
     *
     * @return int
     */
    public function getFeedData($products, $parents, $config, $parentRelations, $data = false)
    {
        $qty = 0;
        foreach ($products as $product) {
            $parent = null;
            if (!empty($parentRelations[$product->getEntityId()])) {
                foreach ($parentRelations[$product->getEntityId()] as $parentId) {
                    if ($parent = $parents->getItemById($parentId)) {
                        continue;
                    }
                }
            }

            $dataRow = $this->productHelper->getDataRow($product, $parent, $config);
            if (empty($dataRow)) {
                continue;
            }

            if ($feedRow = $this->sourceHelper->reformatData($dataRow, $product, $config)) {
                $this->feedHelper->writeRow($feedRow);
                $qty++;
            }

            if ($data) {
                $productData = $this->sourceHelper->getProductDataXml($product, 'product');
                $this->feedHelper->writeRow($productData);
                if ($parent) {
                    $parentData = $this->sourceHelper->getProductDataXml($parent, 'parent');
                    $this->feedHelper->writeRow($parentData);
                }
            }
        }

        return $qty;
    }
}
