<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model;

use Magmodules\Sooqr\Model\Products as ProductModel;
use Magmodules\Sooqr\Helper\Source as SourceHelper;
use Magmodules\Sooqr\Helper\Product as ProductHelper;
use Magmodules\Sooqr\Helper\Cms as CmsHelper;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magmodules\Sooqr\Helper\Feed as FeedHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;

class Generate
{

    const XML_PATH_FEED_RESULT = 'magmodules_sooqr/feeds/results';
    const XML_PATH_GENERATE = 'magmodules_sooqr/generate/enable';

    private $productModel;
    private $sourceHelper;
    private $productHelper;
    private $generalHelper;
    private $feedHelper;
    private $cmsHelper;

    /**
     * Generate constructor.
     *
     * @param Products        $productModel
     * @param SourceHelper    $sourceHelper
     * @param ProductHelper   $productHelper
     * @param CmsHelper       $cmsHelper
     * @param GeneralHelper   $generalHelper
     * @param FeedHelper      $feedHelper
     * @param Emulation       $appEmulation
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductModel $productModel,
        SourceHelper $sourceHelper,
        ProductHelper $productHelper,
        CmsHelper $cmsHelper,
        GeneralHelper $generalHelper,
        FeedHelper $feedHelper,
        Emulation $appEmulation,
        LoggerInterface $logger
    ) {
        $this->productModel = $productModel;
        $this->sourceHelper = $sourceHelper;
        $this->productHelper = $productHelper;
        $this->cmsHelper = $cmsHelper;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->appEmulation = $appEmulation;
        $this->logger = $logger;
    }

    /**
     * Generate all feeds
     */
    public function generateAll()
    {
        $storeIds = $this->generalHelper->getEnabledArray(self::XML_PATH_GENERATE);
        foreach ($storeIds as $storeId) {
            $this->generateByStore($storeId, 'cron');
        }
    }

    /**
     * @param        $storeId
     * @param string $type
     * @param array  $productIds
     * @param int    $page
     *
     * @return array
     */
    public function generateByStore($storeId, $type = 'manual', $productIds = [], $page = 1)
    {
        $timeStart = microtime(true);
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->sourceHelper->getConfig($storeId, $type);
        $header = $this->feedHelper->getFeedHeader();
        $header = $this->sourceHelper->getXmlFromArray($header, 'config');
        $this->feedHelper->createFeed($config, $header);

        $products = $this->productModel->getCollection($config, $productIds, $page);
        $relations = $config['filters']['relations'];
        $limit = $config['filters']['limit'];
        $count = 0;

        foreach ($products as $product) {
            $parent = '';
            if ($relations) {
                if ($parentId = $this->productHelper->getParentId($product->getEntityId())) {
                    $parent = $products->getItemById($parentId);
                    if (!$parent) {
                        $parent = $this->productModel->loadParentProduct($parentId, $config['attributes']);
                    }
                }
            }
            if ($dataRow = $this->productHelper->getDataRow($product, $parent, $config)) {
                if ($row = $this->sourceHelper->reformatData($dataRow, $product, $config)) {
                    $this->feedHelper->writeRow($row);
                    $count++;
                }
            }
        }

        if ($cmsPages = $this->cmsHelper->getCmsPages()) {
            foreach ($cmsPages as $item) {
                $row = $this->sourceHelper->getXmlFromArray($item, 'item');
                $this->feedHelper->writeRow($row);
            }
        }

        $results = $this->feedHelper->getFeedResults($timeStart, $count, $limit);
        $footer = $this->sourceHelper->getXmlFromArray($results, 'results');

        $this->feedHelper->writeFooter($footer);
        $this->feedHelper->updateResult($storeId, $count, $results['processing_time'], $results['date_created'], $type);

        $this->appEmulation->stopEnvironmentEmulation();

        return [
            'status' => 'success',
            'qty'    => $count,
            'path'   => $config['feed_locations']['full_path'],
            'url'    => $config['feed_locations']['url']
        ];
    }
}
