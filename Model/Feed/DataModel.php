<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Feed;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magmodules\Sooqr\Api\Feed\Data\DataInterface;
use Magmodules\Sooqr\Api\Feed\Data\DataInterfaceFactory;

/**
 * Class DataModel
 *
 * Data model for feed
 */
class DataModel extends AbstractModel implements ExtensibleDataInterface, DataInterface
{

    /**
     * @var string
     */
    protected $_eventPrefix = 'sooqr_feed';

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
        $feed = $this->getData();
        $feedObject = $this->dataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $feedObject,
            $feed,
            DataInterface::class
        );

        return $feedObject;
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(int $storeId): DataInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getResult(): string
    {
        return (string)$this->getData(self::RESULT);
    }

    /**
     * @inheritDoc
     */
    public function setResult(string $result): DataInterface
    {
        return $this->setData(self::RESULT, $result);
    }

    /**
     * @inheritDoc
     */
    public function getExecutionTime(): int
    {
        return (int)$this->getData(self::EXECUTION_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setExecutionTime(int $executionTime): DataInterface
    {
        return $this->setData(self::EXECUTION_TIME, $executionTime);
    }

    /**
     * @inheritDoc
     */
    public function setType(int $type): DataInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getExecutedBy(): int
    {
        return (int)$this->getData(self::EXECUTED_BY);
    }

    /**
     * @inheritDoc
     */
    public function setExecutedBy($executedBy): DataInterface
    {
        return $this->setData(self::EXECUTED_BY, $executedBy);
    }

    /**
     * @inheritDoc
     */
    public function getStartedAt(): string
    {
        return (string)$this->getData(self::STARTED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setStartedAt(string $startedAt): DataInterface
    {
        return $this->setData(self::STARTED_AT, $startedAt);
    }

    /**
     * @inheritDoc
     */
    public function getFinishedAt(): string
    {
        return (string)$this->getData(self::FINISHED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setFinishedAt(string $finishedAt): DataInterface
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }

    /**
     * @inheritDoc
     */
    public function getDownloadAt(): string
    {
        return (string)$this->getData(self::DOWNLOADED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setDownloadAt(string $downloadAt): DataInterface
    {
        return $this->setData(self::DOWNLOADED_AT, $downloadAt);
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): ?string
    {
        return $this->getData(self::FILENAME);
    }

    /**
     * @inheritDoc
     */
    public function setFilename(string $filename): DataInterface
    {
        return $this->setData(self::FILENAME, $filename);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookUrl(): ?string
    {
        return $this->getData(self::WEBHOOK_URL);
    }

    /**
     * @inheritDoc
     */
    public function setWebhookUrl(string $url): DataInterface
    {
        return $this->setData(self::WEBHOOK_URL, $url);
    }

    /**
     * @inheritDoc
     */
    public function getResponse(): string
    {
        return (string)$this->getData(self::RESPONSE);
    }

    /**
     * @inheritDoc
     */
    public function getSentToPlatform(): bool
    {
        return (bool)$this->getData(self::SENT_TO_PLATFORM);
    }

    /**
     * @inheritDoc
     */
    public function getType(): int
    {
        return (int)$this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setResponse(string $response): DataInterface
    {
        return $this->setData(self::RESPONSE, $response);
    }

    /**
     * @inheritDoc
     */
    public function setSentToPlatform(bool $sentToPlatform): DataInterface
    {
        return $this->setData(self::SENT_TO_PLATFORM, $sentToPlatform);
    }

    /**
     * @inheritDoc
     */
    public function getCategories(): bool
    {
        return (bool)$this->getData(self::CATEGORIES);
    }

    /**
     * @inheritDoc
     */
    public function setCategories(bool $categories): DataInterface
    {
        return $this->setData($categories, self::CATEGORIES);
    }

    /**
     * @inheritDoc
     */
    public function getCmsPages(): bool
    {
        return (bool)$this->getData(self::CMS_PAGES);
    }

    /**
     * @inheritDoc
     */
    public function setCmsPages(bool $cmsPages): DataInterface
    {
        return $this->setData($cmsPages, self::CMS_PAGES);
    }

    /**
     * @inheritDoc
     */
    public function getProducts(): bool
    {
        return (bool)$this->getData(self::PRODUCTS);
    }

    /**
     * @inheritDoc
     */
    public function setProducts(bool $products): DataInterface
    {
        return $this->setData($products, self::PRODUCTS);
    }
}
