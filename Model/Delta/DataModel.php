<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Delta;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magmodules\Sooqr\Api\Delta\Data\DataInterface;
use Magmodules\Sooqr\Api\Delta\Data\DataInterfaceFactory;

/**
 * Class DataModel
 *
 * Data model for Delta
 */
class DataModel extends AbstractModel implements ExtensibleDataInterface, DataInterface
{

    /**
     * @var string
     */
    protected $_eventPrefix = 'sooqr_delta';
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;
    /**
     * @var DataInterfaceFactory
     */
    protected $dataFactory;

    /**
     * DataModel constructor.
     * @param Context $context
     * @param Registry $registry
     * @param DataInterfaceFactory $dataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel $resource
     * @param Collection $collection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataInterfaceFactory $dataFactory,
        DataObjectHelper $dataObjectHelper,
        ResourceModel $resource,
        Collection $collection,
        array $data = []
    ) {
        $this->dataFactory = $dataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $collection, $data);
    }

    /**
     * Retrieves Invoice model with Invoice data
     *
     * @return DataInterface
     */
    public function getDataModel()
    {
        $DeltaLog = $this->getData();
        $DeltaLogObject = $this->dataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $DeltaLogObject,
            $DeltaLog,
            DataInterface::class
        );

        return $DeltaLogObject;
    }

    /**
     * @inheritDoc
     */
    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId(int $productId): DataInterface
    {
        return $this->setData($productId, self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): DataInterface
    {
        return $this->setData($updatedAt, self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getProductUpdatedAt(): string
    {
        return (string)$this->getData(self::PRODUCT_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setProductUpdatedAt(string $productUpdatedAt): DataInterface
    {
        return $this->setData($productUpdatedAt, self::PRODUCT_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getStockUpdatedAt(): string
    {
        return (string)$this->getData(self::STOCK_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setStockUpdatedAt(string $stockUpdatedAt): DataInterface
    {
        return $this->setData($stockUpdatedAt, self::STOCK_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getDeleted(): bool
    {
        return (bool)$this->getData(self::DELETED);
    }

    /**
     * @inheritDoc
     */
    public function setDeleted(bool $deleted): DataInterface
    {
        return $this->setData($deleted, self::DELETED);
    }

    /**
     * @inheritDoc
     */
    public function getStockStatus(): int
    {
        return (int)$this->getData(self::STOCK_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStockStatus(int $stockStatus): DataInterface
    {
        return $this->setData($stockStatus, self::STOCK_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function getStockStatusUpdatedAt(): string
    {
        return (string)$this->getData(self::STOCK_STATUS_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setStockStatusUpdatedAt(string $stockStatusUpdatedAt): DataInterface
    {
        return $this->setData($stockStatusUpdatedAt, self::STOCK_STATUS_UPDATED_AT);
    }
}
