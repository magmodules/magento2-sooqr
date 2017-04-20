<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magmodules\Sooqr\Model;

use Magmodules\Sooqr\Model\Products as ProductsModel;
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

    private $products;
    private $source;
    private $product;
    private $general;
    private $feed;

    /**
     * Generate constructor.
     *
     * @param Products        $products
     * @param SourceHelper    $source
     * @param ProductHelper   $product
     * @param CmsHelper       $cms
     * @param GeneralHelper   $general
     * @param FeedHelper      $feed
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductsModel $products,
        SourceHelper $source,
        ProductHelper $product,
        CmsHelper $cms,
        GeneralHelper $general,
        FeedHelper $feed,
        Emulation $appEmulation,
        LoggerInterface $logger
    ) {
        $this->products = $products;
        $this->source = $source;
        $this->product = $product;
        $this->cms = $cms;
        $this->general = $general;
        $this->feed = $feed;
        $this->appEmulation = $appEmulation;
        $this->logger = $logger;
    }

    /**
     * Generate all feeds
     */
    public function generateAll()
    {
        $storeIds = $this->general->getEnabledArray(self::XML_PATH_GENERATE);
        foreach ($storeIds as $storeId) {
            $this->generateByStore($storeId, 'cron');
        }
    }

    /**
     * @param $storeId
     * @param string $type
     * @return array
     */
    public function generateByStore($storeId, $type = 'manual')
    {
        $timeStart = microtime(true);
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->source->getConfig($storeId, $type);
        $header = $this->feed->getFeedHeader();
        $header = $this->source->getXmlFromArray($header, 'config');
        $this->feed->createFeed($config, $header);

        $products = $this->products->getCollection($config);
        $relations = $config['filters']['relations'];
        $limit = $config['filters']['limit'];
        $count = 0;

        foreach ($products as $product) {
            $parent = '';
            if ($relations) {
                if ($parentId = $this->product->getParentId($product->getEntityId())) {
                    $parent = $products->getItemById($parentId);
                    if (!$parent) {
                        $parent = $this->products->loadParentProduct($parentId, $config['attributes']);
                    }
                }
            }
            if ($dataRow = $this->product->getDataRow($product, $parent, $config)) {
                if ($row = $this->source->reformatData($dataRow, $product, $config)) {
                    $this->feed->writeRow($row);
                    $count++;
                }
            }
        }

        if ($cmsPages = $this->cms->getCmsPages()) {
            foreach ($cmsPages as $item) {
                $row = $this->source->getXmlFromArray($item, 'item');
                $this->feed->writeRow($row);
            }
        }

        $results = $this->feed->getFeedResults($timeStart, $count, $limit);
        $footer = $this->source->getXmlFromArray($results, 'results');

        $this->feed->writeFooter($footer);
        $this->feed->updateResult($storeId, $count, $results['processing_time'], $results['date_created'], $type);

        $this->appEmulation->stopEnvironmentEmulation();

        return [
            'status' => 'success',
            'qty' => $count,
            'path' => $config['feed_locations']['path'],
            'url' => $config['feed_locations']['url']
        ];
    }
}
