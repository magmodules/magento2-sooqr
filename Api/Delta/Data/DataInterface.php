<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Delta\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for feed
 * @api
 */
interface DataInterface extends ExtensibleDataInterface
{

    public const ENTITY_ID = 'entity_id';
    public const PRODUCT_ID = 'product_id';
    public const UPDATED_AT = 'updated_at';
    public const PRODUCT_UPDATED_AT = 'product_updated_at';
    public const STOCK_UPDATED_AT = 'stock_updated_at';
    public const STOCK_STATUS = 'stock_status';
    public const STOCK_STATUS_UPDATED_AT = 'stock_status_updated_at';
    public const DELETED = 'deleted';

    /**
     * Get ID of Product
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Set ID of Product
     *
     * @param int $productId
     *
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Get updated time
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set updated time
     *
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * Get updated time
     *
     * @return string
     */
    public function getProductUpdatedAt(): string;

    /**
     * Set updated time
     *
     * @param string $productUpdatedAt
     *
     * @return $this
     */
    public function setProductUpdatedAt(string $productUpdatedAt): self;

    /**
     * Get updated time
     *
     * @return string
     */
    public function getStockUpdatedAt(): string;

    /**
     * Set updated time
     *
     * @param string $stockUpdatedAt
     *
     * @return $this
     */
    public function setStockUpdatedAt(string $stockUpdatedAt): self;

    /**
     * Get stock status
     *
     * @return int
     */
    public function getStockStatus(): int;

    /**
     * Set updated time
     *
     * @param int $stockStatus
     *
     * @return $this
     */
    public function setStockStatus(int $stockStatus): self;

    /**
     * Get stock status updated time
     *
     * @return string
     */
    public function getStockStatusUpdatedAt(): string;

    /**
     * Set stock status updated time
     *
     * @param string $stockStatusUpdatedAt
     *
     * @return $this
     */
    public function setStockStatusUpdatedAt(string $stockStatusUpdatedAt): self;

    /**
     * Get Deleted data
     *
     * @return bool
     */
    public function getDeleted(): bool;

    /**
     * Set Deleted data
     *
     * @param bool $deleted
     *
     * @return $this
     */
    public function setDeleted(bool $deleted): self;
}
