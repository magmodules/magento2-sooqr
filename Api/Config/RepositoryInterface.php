<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Config;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magmodules\Sooqr\Api\Config\System\DataInterface;

/**
 * Config repository interface
 */
interface RepositoryInterface extends DataInterface
{

    /** Module's extension code */
    public const EXTENSION_CODE = 'Magmodules_Sooqr';

    /** General Group */
    public const XML_PATH_EXTENSION_ENABLE = 'sooqr_general/general/enable';
    public const XML_PATH_EXTENSION_VERSION = 'sooqr_general/general/version';
    public const XML_PATH_CLEANUP_OFFSET = 'sooqr_data/general/cleanup_offset';
    public const XML_PATH_DEBUG = 'sooqr_general/debug/enable';

    /** Credentials Group */
    public const XML_PATH_ENVIRONMENT = 'sooqr_general/credentials/environment';
    public const XML_PATH_ACCOUNT_ID = 'sooqr_general/credentials/account_id';
    public const XML_PATH_API_KEY = 'sooqr_general/credentials/api_key';

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Get extension version
     *
     * @return string
     */
    public function getExtensionVersion(): string;

    /**
     * Get Magento Version
     *
     * @return string
     */
    public function getMagentoVersion(): string;

    /**
     * Returns Sooqr credentials
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCredentials(int $storeId = null): array;

    /**
     * @param int $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFeedHeader(int $storeId): array;

    /**
     * @param int $qty
     * @return array
     */
    public function getFeedFooter(int $qty): array;

    /**
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isInProduction(int $storeId = null): bool;

    /**
     * Get current or specified store
     *
     * @param int|null $storeId
     *
     * @return StoreInterface
     */
    public function getStore(int $storeId = null): StoreInterface;

    /**
     * Returns true if debug log is enabled
     *
     * @return bool
     */
    public function logDebug(): bool;

    /**
     * Gte offset in days for files cleanup
     *
     * @return int
     */
    public function getCleanupOffset(): int;
}
