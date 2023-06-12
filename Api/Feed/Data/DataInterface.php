<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Feed\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for feed
 * @api
 */
interface DataInterface extends ExtensibleDataInterface
{

    public const ENTITY_ID = 'entity_id';
    public const STORE_ID = 'store_id';
    public const RESULT = 'result';
    public const EXECUTION_TIME = 'execution_time';
    public const TYPE = 'type';
    public const EXECUTED_BY = 'executed_by';
    public const STARTED_AT = 'started_at';
    public const FINISHED_AT = 'finished_at';
    public const DOWNLOADED_AT = 'downloaded_at';
    public const FILENAME = 'filename';
    public const WEBHOOK_URL = 'webhook_url';
    public const SENT_TO_PLATFORM = 'sent_to_platform';
    public const RESPONSE = 'response';
    public const PRODUCTS = 'products';
    public const CMS_PAGES = 'cms_pages';
    public const CATEGORIES = 'categories';

    /**
     * Get ID of store
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Set ID of store
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * Get result
     *
     * @return string
     */
    public function getResult(): string;

    /**
     * Set result
     *
     * @param string $result
     *
     * @return $this
     */
    public function setResult(string $result): self;

    /**
     * Get result
     *
     * @return int
     */
    public function getExecutionTime(): int;

    /**
     * Set result
     *
     * @param int $executionTime
     *
     * @return $this
     */
    public function setExecutionTime(int $executionTime): self;

    /**
     * Get type
     *
     * @return int
     */
    public function getType(): int;

    /**
     * Set type
     *
     * @param int $type
     *
     * @return $this
     */
    public function setType(int $type): self;

    /**
     * Get executed by
     *
     * @return int
     */
    public function getExecutedBy(): int;

    /**
     * Set executed by
     *
     * @param $executedBy
     *
     * @return $this
     */
    public function setExecutedBy($executedBy): self;

    /**
     * Set is data update was pushed to platform
     *
     * @param bool $sentToPlatform
     *
     * @return $this
     */
    public function setSentToPlatform(bool $sentToPlatform): self;

    /**
     * Get is data update was pushed to platform
     *
     * @return bool
     */
    public function getSentToPlatform(): bool;

    /**
     * Get start time
     *
     * @return string
     */
    public function getStartedAt(): string;

    /**
     * Set start time
     *
     * @param string $startedAt
     *
     * @return $this
     */
    public function setStartedAt(string $startedAt): self;

    /**
     * Get finish time
     *
     * @return string
     */
    public function getFinishedAt(): string;

    /**
     * Set finish time
     *
     * @param string $finishedAt
     *
     * @return $this
     */
    public function setFinishedAt(string $finishedAt): self;

    /**
     * Get download time
     *
     * @return string
     */
    public function getDownloadAt(): string;

    /**
     * Set download time
     *
     * @param string $downloadAt
     *
     * @return $this
     */
    public function setDownloadAt(string $downloadAt): self;

    /**
     * Get file name
     *
     * @return string
     */
    public function getFilename(): ?string;

    /**
     * Set file name
     *
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename(string $filename): self;

    /**
     * Get webhook url
     *
     * @return string
     */
    public function getWebhookUrl(): ?string;

    /**
     * Set webhook url
     *
     * @param string $url
     *
     * @return $this
     */
    public function setWebhookUrl(string $url): self;

    /**
     * Get response result
     *
     * @return string
     */
    public function getResponse(): string;

    /**
     * Set response result
     *
     * @param string $response
     *
     * @return $this
     */
    public function setResponse(string $response): DataInterface;

    /**
     * Get if Categories generated
     *
     * @return bool
     */
    public function getCategories(): bool;

    /**
     * Set if Categories generated
     *
     * @param bool $categories
     *
     * @return $this
     */
    public function setCategories(bool $categories): self;

    /**
     * Get if CMS pages generated
     *
     * @return bool
     */
    public function getCmsPages(): bool;

    /**
     * Set if CMS pages generated
     *
     * @param bool $cmsPages
     *
     * @return $this
     */
    public function setCmsPages(bool $cmsPages): self;

    /**
     * Get if products generated
     *
     * @return bool
     */
    public function getProducts(): bool;

    /**
     * Set if products generated
     *
     * @param bool $products
     *
     * @return $this
     */
    public function setProducts(bool $products): self;
}
