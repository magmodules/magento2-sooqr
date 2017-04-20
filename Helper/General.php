<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Magmodules_Sooqr';
    const XML_PATH_EXTENSION_ENABLED = 'magmodules_sooqr/general/enable';
    const XML_PATH_FRONTEND_ENABLED = 'magmodules_sooqr/implementation/enable';
    const XML_PATH_API_KEY = 'magmodules_sooqr/implementation/api_key';
    const XML_PATH_ACCOUNT_ID = 'magmodules_sooqr/implementation/account_id';
    const XML_PATH_PARENT = 'magmodules_sooqr/implementation/advanced_parent';
    const XML_PATH_VERSION = 'magmodules_sooqr/implementation/advanced_version';
    const XML_PATH_CUSTOM_JS = 'magmodules_sooqr/implementation/advanced_custom_js';
    const XML_PATH_STATISTICS = 'magmodules_sooqr/implementation/statistics';
    const XML_PATH_STAGING = 'magmodules_sooqr/implementation/advanced_staging';
    const XML_PATH_TOKEN = 'magmodules_sooqr/general/token';

    private $moduleList;
    private $metadata;
    private $storeManager;
    private $objectManager;
    private $config;

    /**
     * General constructor.
     *
     * @param Context                  $context
     * @param ObjectManagerInterface   $objectManager
     * @param StoreManagerInterface    $storeManager
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $metadata
     * @param Config                   $config
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        Config $config
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * @param null $storeId
     *
     * @return bool|mixed
     */
    public function getFrontendEnabled($storeId = null)
    {
        if (!$this->getEnabled($storeId)) {
            return false;
        }

        if (!$this->getAccountId($storeId) || !$this->getApiKey($storeId)) {
            return false;
        }

        return $this->getStoreValue(self::XML_PATH_FRONTEND_ENABLED, $storeId);
    }

    /**
     * General check if Extension is enabled
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEnabled($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_ENABLED, $storeId);
    }

    /**
     * Get Configuration data
     *
     * @param      $path
     * @param      $scope
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStoreValue($path, $storeId = null, $scope = null)
    {
        if (empty($scope)) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getAccountId($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_ACCOUNT_ID, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getApiKey($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_API_KEY, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getParent($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_PARENT, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getVersion($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_VERSION, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCustomJs($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_CUSTOM_JS, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStatistics($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_STATISTICS, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStaging($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_STAGING, $storeId);
    }

    /**
     * Set configuration data function
     *
     * @param      $value
     * @param      $key
     * @param null $storeId
     */
    public function setConfigData($value, $key, $storeId = null)
    {
        if ($storeId) {
            $this->config->saveConfig($key, $value, 'stores', $storeId);
        } else {
            $this->config->saveConfig($key, $value, 'default', 0);
        }
    }

    /**
     * Returns current version of the extension
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }

    /**
     * Returns current version of Magento
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->metadata->getVersion();
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->getStoreValue(self::XML_PATH_TOKEN);
    }

    /**
     * @param $path
     *
     * @return array
     */
    public function getEnabledArray($path = self::XML_PATH_EXTENSION_ENABLED)
    {
        $storeIds = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($this->getStoreValue($path)) {
                if ($this->getEnabled($store->getId())) {
                    $storeIds[] = $store->getId();
                }
            }
        }

        return $storeIds;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getCurrecyCode($storeId = null)
    {
        return $this->storeManager->getStore($storeId)->getCurrentCurrency()->getCode();
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getStoreName($storeId = null)
    {
        $baseUrl = $this->getBaseUrl($storeId);
        return str_replace(['https://','http://','www'], '', $baseUrl);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getBaseUrl($storeId = null)
    {
        return $this->storeManager->getStore($storeId)->getBaseUrl();
    }

    /**
     * @param $storeId
     * @param $param
     *
     * @return mixed
     */
    public function getUrl($storeId, $param)
    {
        return $this->storeManager->getStore($storeId)->getUrl($param);
    }
}
