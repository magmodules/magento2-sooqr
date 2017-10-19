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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

class Feed extends AbstractHelper
{

    const DEFAULT_FILENAME = 'sooqr.xml';
    const DEFAULT_DIRECTORY = 'sooqr';
    const DEFAULT_DIRECTORY_PATH = 'pub/media/sooqr';
    const XPATH_GENERATE_ENABLED = 'magmodules_sooqr/generate/enabled';
    const XPATH_FEED_URL = 'magmodules_sooqr/feeds/url';
    const XPATH_FEED_RESULT = 'magmodules_sooqr/feeds/results';
    const XPATH_FEED_FILENAME = 'magmodules_sooqr/generate/filename';

    /**
     * @var General
     */
    private $generalHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $directory;

    /**
     * @var
     */
    private $stream;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var DateTime
     */
    private $datetime;

    /**
     * @var null|string
     */
    private $baseDir = null;

    /**
     * Feed constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Filesystem            $filesystem
     * @param DirectoryList         $directoryList
     * @param DateTime              $datetime
     * @param TimezoneInterface     $timezone
     * @param General               $generalHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        DateTime $datetime,
        TimezoneInterface $timezone,
        GeneralHelper $generalHelper
    ) {
        $this->generalHelper = $generalHelper;
        $this->storeManager = $storeManager;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->baseDir = $directoryList->getPath(DirectoryList::ROOT);
        $this->timezone = $timezone;
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
            $location = $this->getFeedLocation($storeId);
            $feedData[$storeId] = [
                'store_id'  => $storeId,
                'code'      => $store->getCode(),
                'name'      => $store->getName(),
                'is_active' => $store->getIsActive(),
                'status'    => $this->generalHelper->getStoreValue(self::XPATH_GENERATE_ENABLED, $storeId),
                'feed'      => $this->getFeedUrl($storeId),
                'full_path' => (!empty($location['full_path']) ? $location['full_path'] : ''),
                'result'    => $this->generalHelper->getUncachedStoreValue(self::XPATH_FEED_RESULT, $storeId),
                'available' => (!empty($location['full_path']) ? file_exists($location['full_path']) : false)
            ];
        }
        return $feedData;
    }

    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     */
    public function getFeedLocation($storeId, $type = '')
    {
        $fileName = $this->generalHelper->getStoreValue(self::XPATH_FEED_FILENAME, $storeId);

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
     * @param $storeId
     * @param $processed
     * @param $time
     * @param $date
     * @param $type
     * @param $pages
     */
    public function updateResult($storeId, $processed, $time, $date, $type, $pages)
    {
        if (empty($type)) {
            $type = 'manual';
        }

        if ($type != 'preview') {
            $pages--;
            if ($pages > 1) {
                $html = __(
                    'Date: %1 (%2) - Products: %3 (%4 pages) - Time: %5',
                    $date,
                    $type,
                    $processed,
                    $pages,
                    $time
                );
            } else {
                $html = __('Date: %1 (%2) - Products: %3 - Time: %4', $date, $type, $processed, $time);
            }
            $this->generalHelper->setConfigData($html, self::XPATH_FEED_RESULT, $storeId);
        }
    }

    /**
     * @param $row
     */
    public function writeRow($row)
    {
        $row = $this->stripInvalidXml($row);
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
        $summary['extension_version'] = $this->generalHelper->getExtensionVersion();
        $summary['token'] = $this->generalHelper->getToken();
        $summary['magento_version'] = $this->generalHelper->getMagentoVersion();
        $summary['base_url'] = $this->generalHelper->getBaseUrl();

        return $summary;
    }

    /**
     *
     * @param $value
     *
     * @return string
     */
    public function stripInvalidXml($value)
    {
        $regex = '/(
            [\xC0-\xC1]
            | [\xF5-\xFF]
            | \xE0[\x80-\x9F]
            | \xF0[\x80-\x8F]
            | [\xC2-\xDF](?![\x80-\xBF])
            | [\xE0-\xEF](?![\x80-\xBF]{2})
            | [\xF0-\xF4](?![\x80-\xBF]{3})
            | (?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF]
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF]
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF])
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2})
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF])
        )/x';
        $value = preg_replace($regex, '', $value);
        $return = '';
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))
            ) {
                $return .= chr($current);
            } else {
                $return .= ' ';
            }
        }
        return $return;
    }

    /**
     * @param $timeStart
     * @param $processed
     * @param $limit
     *
     * @return array
     */
    public function getFeedResults($timeStart, $processed, $limit)
    {
        $summary = [];
        $summary['products_total'] = $processed;
        $summary['products_limit'] = $limit;
        $summary['processing_time'] = number_format((microtime(true) - $timeStart), 2) . ' sec';
        $summary['date_created'] = $this->timezone->date($this->datetime->date())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
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
            $json['feeds'][$storeId]['name'] = $this->generalHelper->getStoreName($storeId);
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

    /**
     * @param $page
     * @param $pages
     * @param $storeId
     */
    public function addLog($page, $pages, $storeId)
    {
        $memUsage = memory_get_usage(true);
        if ($memUsage < 1024) {
            $usage = $memUsage . ' b';
        } elseif ($memUsage < 1048576) {
            $usage = round($memUsage / 1024, 2) . ' KB';
        } else {
            $usage = round($memUsage / 1048576, 2) . ' MB';
        }

        $msg = $page . '/' . $pages . ': ' . $usage;
        $this->generalHelper->addTolog('Feed Generation StoreId: ' . $storeId, $msg);
    }
}
