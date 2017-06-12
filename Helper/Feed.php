<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

class Feed extends AbstractHelper
{

    const DEFAULT_FILENAME = 'sooqr.xml';
    const DEFAULT_DIRECTORY = 'sooqr';
    const DEFAULT_DIRECTORY_PATH = 'pub/media/sooqr';
    const XML_PATH_GENERATE_ENABLED = 'magmodules_sooqr/generate/enabled';
    const XML_PATH_FEED_URL = 'magmodules_sooqr/feeds/url';
    const XML_PATH_FEED_RESULT = 'magmodules_sooqr/feeds/results';
    const XML_PATH_FEED_FILENAME = 'magmodules_sooqr/generate/filename';

    private $generalHelper;
    private $storeManager;
    private $directory;
    private $stream;
    private $datetime;
    private $baseDir = null;

    /**
     * Feed constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Filesystem            $filesystem
     * @param DirectoryList         $directoryList
     * @param DateTime              $datetime
     * @param General               $generalHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        DateTime $datetime,
        GeneralHelper $generalHelper
    ) {
        $this->generalHelper = $generalHelper;
        $this->storeManager = $storeManager;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->baseDir = $directoryList->getPath(DirectoryList::ROOT);
        $this->datetime = $datetime;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getConfigData()
    {
        $feedData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            $feedData[$storeId] = [
                'store_id'  => $storeId,
                'code'      => $store->getCode(),
                'name'      => $store->getName(),
                'is_active' => $store->getIsActive(),
                'status'    => $this->generalHelper->getStoreValue(self::XML_PATH_GENERATE_ENABLED, $storeId),
                'feed'      => $this->getFeedUrl($storeId),
                'result'    => $this->generalHelper->getStoreValue(self::XML_PATH_FEED_RESULT, $storeId),
            ];
        }
        return $feedData;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getFeedUrl($storeId)
    {
        if ($location = $this->getFeedLocation($storeId)) {
            return $location['url'];
        }

        return false;
    }

    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     */
    public function getFeedLocation($storeId, $type = '')
    {
        $fileName = $this->generalHelper->getStoreValue(self::XML_PATH_FEED_FILENAME, $storeId);

        if (empty($fileName)) {
            $fileName = self::DEFAULT_FILENAME;
        }

        if ($type == 'preview') {
            $extra = '-' . $storeId . '-preview.xml';
        } else {
            $extra = '-' . $storeId . '.xml';
        }

        if (substr($fileName, -3) != 'xml') {
            $fileName = $fileName . $extra;
        } else {
            $fileName = substr($fileName, 0, -4) . $extra;
        }

        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $feedUrl = $mediaUrl . self::DEFAULT_DIRECTORY;

        $location = [];
        $location['path'] = self::DEFAULT_DIRECTORY_PATH . '/' . $fileName;
        $location['full_path'] = $this->baseDir . '/' . self::DEFAULT_DIRECTORY_PATH . '/' . $fileName;
        $location['url'] = $feedUrl . '/' . $fileName;
        $location['file_name'] = $fileName;
        $location['base_dir'] = self::DEFAULT_DIRECTORY_PATH;

        return $location;
    }

    /**
     * @param $storeId
     * @param $qty
     * @param $time
     * @param $date
     * @param $type
     */
    public function updateResult($storeId, $qty, $time, $date, $type)
    {
        if (empty($type)) {
            $type = 'manual';
        }
        $html = sprintf('Date: %s (%s) - Products: %s - Time: %s', $date, $type, $qty, $time);
        $this->generalHelper->setConfigData($html, self::XML_PATH_FEED_RESULT, $storeId);
    }

    /**
     * @param $row
     */
    public function writeRow($row)
    {
        $this->getStream()->write($row);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getStream()
    {
        if ($this->stream) {
            return $this->stream;
        } else {
            throw new LocalizedException(__('File handler unreachable'));
        }
    }

    /**
     * @param $config
     * @param $headerConfig
     */
    public function createFeed($config, $headerConfig)
    {
        $path = $config['feed_locations']['path'];
        $this->stream = $this->directory->openFile($path);

        $header = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
        $header .= '<rss xmlns:sqr="http://base.sooqr.com/ns/1.0" version="2.0" encoding="utf-8">' . PHP_EOL;
        $header .= $headerConfig;
        $header .= ' <products>' . PHP_EOL;

        $this->getStream()->write($header);
    }

    /**
     * @param $summary
     */
    public function writeFooter($summary)
    {
        $footer = ' </products>' . PHP_EOL;
        $footer .= $summary;
        $footer .= '</rss>' . PHP_EOL;
        $this->getStream()->write($footer);
    }

    /**
     * @return array
     */
    public function getFeedHeader()
    {
        $summary = [];
        $summary['system'] = 'Magento 2';
        $summary['extension'] = GeneralHelper::MODULE_CODE;
        $summary['version'] = $this->generalHelper->getExtensionVersion();
        $summary['token'] = $this->generalHelper->getToken();
        $summary['magento_version'] = $this->generalHelper->getMagentoVersion();
        $summary['base_url'] = $this->generalHelper->getBaseUrl();

        return $summary;
    }

    /**
     * @param $timeStart
     * @param $count
     * @param $limit
     *
     * @return array
     */
    public function getFeedResults($timeStart, $count, $limit)
    {
        $summary = [];
        $summary['products_total'] = $count;
        $summary['products_limit'] = $limit;
        $summary['processing_time'] = number_format((microtime(true) - $timeStart), 2) . ' sec';
        $summary['date_created'] = $this->datetime->gmtDate();
        return $summary;
    }

    /**
     * @return array
     */
    public function getInstallation()
    {
        $json = [];
        $storeIds = $this->generalHelper->getEnabledArray();

        foreach ($storeIds as $storeId) {
            $feedLocacation = $this->getFeedLocation($storeId);
            if (isset($feedLocacation['url'])) {
                $feedUrl = $feedLocacation['url'];
            } else {
                $feedUrl = '';
            }
            $json['feeds'][$storeId]['name'] =  $this->generalHelper->getStoreName($storeId);
            $json['feeds'][$storeId]['feed_url'] = $feedUrl;
            $json['feeds'][$storeId]['currency'] = $this->generalHelper->getCurrecyCode($storeId);
            $json['feeds'][$storeId]['locale'] = $this->generalHelper->getStoreValue('general/locale/code', $storeId);
            $json['feeds'][$storeId]['country'] = $this->generalHelper->getStoreValue('general/country/default', $storeId);
            $json['feeds'][$storeId]['timezone'] = $this->generalHelper->getStoreValue('general/locale/timezone', $storeId);
            $json['feeds'][$storeId]['extension'] = 'Magmodules_Sooqr';
            $json['feeds'][$storeId]['platform_version'] = $this->generalHelper->getMagentoVersion();
            $json['feeds'][$storeId]['extension_version'] = $this->generalHelper->getExtensionVersion();
        }
        return $json;
    }
}
