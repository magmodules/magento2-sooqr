<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config;

use Exception;
use Magento\Store\Api\Data\StoreInterface;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Magmodules\Sooqr\Model\Config\System\DataRepository;
use Magmodules\Sooqr\Model\Config\Source\Environment;

/**
 * Config repository class
 */
class Repository extends DataRepository implements ConfigRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function getExtensionVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_VERSION);
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
     * @inheritDoc
     */
    public function getMagentoVersion(): string
    {
        return $this->metadata->getVersion();
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_EXTENSION_ENABLE);
    }

    /**
     * @inheritDoc
     */
    public function logDebug(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(int $storeId = null): array
    {
        return [
            'account_id'  => trim($this->getStoreValue(self::XML_PATH_ACCOUNT_ID, $storeId)),
            'environment' => $this->getStoreValue(self::XML_PATH_ENVIRONMENT, $storeId)
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFeedHeader(int $storeId): array
    {
        return [
            'system' => 'Magento 2',
            'extension' => 'Magmodules_Sooqr',
            'extension_version' => $this->getExtensionVersion(),
            'magento_version' => $this->getMagentoVersion(),
            'base_url' =>  $this->storeManager->getStore($storeId)->getBaseUrl()
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFeedFooter(int $qty): array
    {
        return [
            'products_total' => $qty,
            'date_created' => $this->dateTime->gmtDate()
        ];
    }

    /**
     * @inheritDoc
     */
    public function isInProduction(int $storeId = null): bool
    {
        return $this->getStoreValue(self::XML_PATH_ENVIRONMENT, $storeId) === Environment::PRODUCTION;
    }

    /**
     * @inheritDoc
     */
    public function getCleanupOffset(): int
    {
        return $this->getStoreValue(self::XML_PATH_CLEANUP_OFFSET)
            ? (int)$this->getStoreValue(self::XML_PATH_CLEANUP_OFFSET)
            : 50;
    }
}
