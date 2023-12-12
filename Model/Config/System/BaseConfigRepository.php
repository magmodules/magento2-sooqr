<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config\System;

use Exception;
use Magento\Config\Model\ResourceModel\Config as ConfigData;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Base config repository class
 */
class BaseConfigRepository
{

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Json
     */
    protected $json;
    /**
     * @var ProductMetadataInterface
     */
    protected $metadata;
    /**
     * @var ConfigDataCollectionFactory
     */
    protected $configDataCollectionFactory;
    /**
     * @var ConfigData
     */
    protected $config;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var array
     */
    protected $attributeData = [];
    /**
     * @var ScopeCodeResolver
     */
    protected $scopeCodeResolver;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigDataCollectionFactory $configDataCollectionFactory
     * @param ConfigData $config
     * @param Json $json
     * @param ProductMetadataInterface $metadata
     * @param EncryptorInterface $encryptor
     * @param ResourceConnection $resourceConnection
     * @param ScopeCodeResolver $scopeCodeResolver
     * @param DateTime $datetime
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        ConfigData $config,
        Json $json,
        ProductMetadataInterface $metadata,
        EncryptorInterface $encryptor,
        ResourceConnection $resourceConnection,
        ScopeCodeResolver $scopeCodeResolver,
        DateTime $datetime
    ) {
        $this->storeManager = $storeManager;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->json = $json;
        $this->encryptor = $encryptor;
        $this->metadata = $metadata;
        $this->resourceConnection = $resourceConnection;
        $this->scopeCodeResolver = $scopeCodeResolver;
        $this->dateTime = $datetime;
    }

    /**
     * @inheritDoc
     */
    public function getStore(int $storeId = null): StoreInterface
    {
        try {
            if ($storeId) {
                return $this->storeManager->getStore($storeId);
            } else {
                return $this->storeManager->getStore();
            }
        } catch (Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }
        $stores = $this->storeManager->getStores();
        return reset($stores);
    }

    /**
     * Get Configuration data
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     *
     * @return string
     */
    protected function getStoreValue(
        string $path,
        int $storeId = null,
        string $scope = null
    ): string {
        if (!$storeId) {
            $storeId = (int)$this->getStore()->getId();
        }
        $scope = $scope ?? ScopeInterface::SCOPE_STORE;
        return (string)$this->scopeConfig->getValue($path, $scope, (int)$storeId);
    }

    /**
     * Set Store data
     *
     * @param mixed $value
     * @param string $key
     * @param int|null $storeId
     */
    public function setConfigData($value, string $key, int $storeId = null): void
    {
        if ($storeId) {
            $this->config->saveConfig($key, $value, 'stores', $storeId);
        } else {
            $this->config->saveConfig($key, $value, 'default', 0);
        }
    }

    /**
     * Retrieve config value array by path, storeId and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return array
     */
    protected function getStoreValueArray(string $path, int $storeId = null, string $scope = null): array
    {
        if (!$value = $this->getStoreValue($path, (int)$storeId, $scope)) {
            return [];
        }

        try {
            return $this->json->unserialize($value);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Return uncached store config data
     *
     * @param string $path
     * @param int|null $storeId
     * @return string
     */
    protected function getUncachedStoreValue(string $path, int $storeId = null): string
    {
        $collection = $this->configDataCollectionFactory->create()
            ->addFieldToSelect('value')
            ->addFieldToFilter('path', $path);

        if ($storeId > 0) {
            $collection->addFieldToFilter('scope_id', $storeId);
            $collection->addFieldToFilter('scope', 'stores');
        } else {
            $collection->addFieldToFilter('scope_id', 0);
            $collection->addFieldToFilter('scope', 'default');
        }

        $collection->getSelect()->limit(1);

        return (string)$collection->getFirstItem()->getData('value');
    }

    /**
     * Get config value flag
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     *
     * @return bool
     */
    protected function isSetFlag(string $path, int $storeId = null, string $scope = null): bool
    {
        $scope = $scope ?: ScopeInterface::SCOPE_STORE;
        $storeId = $storeId ?: $this->getStore()->getId();
        return $this->scopeConfig->isSetFlag($path, $scope, $storeId);
    }
}
