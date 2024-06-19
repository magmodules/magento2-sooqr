<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Generate;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\EncoderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Cms\RepositoryInterface as CmsRepository;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Feed\Data\DataInterface as FeedDataInterface;
use Magmodules\Sooqr\Api\Feed\RepositoryInterface as FeedRepository;
use Magmodules\Sooqr\Api\Generate\RepositoryInterface as GenerateRepository;
use Magmodules\Sooqr\Api\ProductData\RepositoryInterface as ProductDataRepository;
use Magmodules\Sooqr\Model\Config\Source\FeedExecBy;
use Magmodules\Sooqr\Model\Config\Source\FeedType;
use Magmodules\Sooqr\Service\Api\Adapter;
use Magmodules\Sooqr\Service\Delta\Get as GetDelta;
use Magmodules\Sooqr\Service\Feed\Create as FeedService;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Feed generate repository class
 */
class Repository implements GenerateRepository
{

    /**
     * Error messages
     */
    public const ERROR_ENABLE = 'Product Feed (or module) for this store ID (%1) is not enabled.';
    public const ERROR_CREDENTIALS = 'Not all credentials are set for store ID %1.';

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var DateTime
     */
    private $datetime;
    /**
     * @var FeedService
     */
    private $feedService;
    /**
     * @var ProductDataRepository
     */
    private $productDataRepository;
    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var CmsRepository
     */
    private $cmsRepository;
    /**
     * @var FeedRepository
     */
    private $feedRepository;
    /**
     * @var GetDelta
     */
    private $getDelta;
    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Encryptor
     */
    private $encryptor;
    /**
     * @var EncoderInterface
     */
    private $encoder;
    /**
     * @var File
     */
    private $file;

    /**
     * Repository constructor.
     *
     * @param ConfigProvider $configProvider
     * @param DateTime $datetime
     * @param FeedService $feedService
     * @param ProductDataRepository $productDataRepository
     * @param DirectoryList $directoryList
     * @param CmsRepository $cmsRepository
     * @param FeedRepository $feedRepository
     * @param GetDelta $getDelta
     * @param Adapter $adapter
     * @param StoreManagerInterface $storeManager
     * @param Encryptor $encryptor
     * @param EncoderInterface $encoder
     * @param File $file
     */
    public function __construct(
        ConfigProvider $configProvider,
        DateTime $datetime,
        FeedService $feedService,
        ProductDataRepository $productDataRepository,
        DirectoryList $directoryList,
        CmsRepository $cmsRepository,
        FeedRepository $feedRepository,
        GetDelta $getDelta,
        Adapter $adapter,
        StoreManagerInterface $storeManager,
        Encryptor $encryptor,
        EncoderInterface $encoder,
        File $file
    ) {
        $this->configProvider = $configProvider;
        $this->datetime = $datetime;
        $this->feedService = $feedService;
        $this->productDataRepository = $productDataRepository;
        $this->directoryList = $directoryList;
        $this->cmsRepository = $cmsRepository;
        $this->feedRepository = $feedRepository;
        $this->getDelta = $getDelta;
        $this->adapter = $adapter;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->encoder = $encoder;
        $this->file = $file;
    }

    /**
     * @param FeedDataInterface $feed
     *
     * @throws LocalizedException
     */
    private function sendNotification(FeedDataInterface $feed)
    {
        if (!$feed->getFilename() || $feed->getSentToPlatform() || $feed->getType() == FeedType::PREVIEW) {
            return;
        }

        $feed->setWebhookUrl($this->getWebhookUrl($feed));

        try {
            $this->adapter->execute(
                [
                    'type' => FeedType::TYPES[$feed->getType()],
                    'store_id' => $feed->getStoreId(),
                    'id' => $feed->getEntityId(),
                    'webhook_url' => $feed->getWebhookUrl()
                ]
            );
            $feed->setResponse('Success');
            $feed->setSentToPlatform(true);
        } catch (\Exception $exception) {
            $feed->setResponse($exception->getMessage());
        }

        $this->feedRepository->save($feed);
    }

    /**
     * @param FeedDataInterface $feed
     *
     * @return string
     */
    private function getWebhookUrl(FeedDataInterface $feed): string
    {
        try {
            return implode('', [
                $this->storeManager->getStore($feed->getStoreId())->getBaseUrl(),
                'rest/V1/sooqr/feed/',
                $this->encoder->encode(
                    $this->encryptor->encrypt($feed->getEntityId())
                ),
            ]);
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function execute(int $storeId, int $type = FeedType::FULL, int $executedBy = FeedExecBy::MANUAL): array
    {
        $timeStart = microtime(true);
        $start = $this->datetime->gmtDate();

        if (!$this->configProvider->isDataEnabled($storeId)) {
            $message = self::ERROR_ENABLE;
            throw new LocalizedException(__($message, $storeId));
        }

//        $credentials = $this->configProvider->getCredentials($storeId);
//        if (count($credentials) != count(array_filter($credentials))) {
//            $message = self::ERROR_CREDENTIALS;
//            throw new LocalizedException(__($message, $storeId));
//        }

        $dataFeed = [];
        $generatedEntities = [];
        switch ($type) {
            case FeedType::DELTA:
                $productIds = $this->getDelta->execute($storeId);
                if ($products = $this->productDataRepository->getProductData($storeId, $productIds, $type)) {
                    $dataFeed = [
                        'config' => $this->configProvider->getFeedHeader($storeId),
                        'products' => $products,
                        'results' => $this->configProvider->getFeedFooter(count($products))
                    ];
                    $generatedEntities[] = 'products';
                }
                break;
            case FeedType::PREVIEW:
                $products = $this->productDataRepository->getProductData($storeId, null, $type);
                $dataFeed = [
                    'config' => $this->configProvider->getFeedHeader($storeId),
                    'products' => $products,
                    'results' => $this->configProvider->getFeedFooter(count($products))
                ];
                $generatedEntities[] = 'products';
                break;
            default:
                $products = $this->productDataRepository->getProductData($storeId, null, $type);
                $dataFeed = [
                    'config' => $this->configProvider->getFeedHeader($storeId),
                    'products' => array_merge($products, $this->cmsRepository->getCmsPages($storeId)),
                    'results' => $this->configProvider->getFeedFooter(count($products))
                ];
                $generatedEntities = ['products', 'categories', 'cms_pages'];
                break;
        }

        if (!empty($dataFeed)) {
            $filePath = $this->getFilePath($type, $storeId, true);
            $this->feedService->execute($dataFeed, $storeId, $filePath);

            if ($type == FeedType::FULL) {
                $this->file->copy($filePath, $this->getFilePath($type, $storeId));
            }

            $resultMsg = sprintf(
                'Feed generated in %s on %s (%s)',
                $this->getTimeUsage($timeStart),
                $this->datetime->gmtDate(),
                FeedType::TYPES[$type]
            );

            $feed = $this->feedRepository->create();
            $feed->setStoreId($storeId)
                ->setExecutionTime((int)round((microtime(true) - $timeStart)))
                ->setResult($resultMsg)
                ->setStartedAt($start)
                ->setFinishedAt($this->datetime->gmtDate())
                ->setType($type)
                ->setExecutedBy($executedBy)
                ->setFilename($filePath);
            foreach ($generatedEntities as $entity) {
                $feed->setData($entity, true);
            }

            $this->feedRepository->save($feed);
            // disable for release 2.0.0
            // $this->sendNotification($feed);
        }

        return [
            'success' => true,
            'message' => sprintf('Store ID %s: %s', $storeId, $resultMsg ?? 'No data found to export'),
            'path' => $filePath ?? null
        ];
    }

    /**
     * @param int $type
     * @param int $storeId
     * @param bool $addTimeStamp
     * @return string
     * @throws FileSystemException
     */
    private function getFilePath(int $type, int $storeId = 0, bool $addTimeStamp = false): string
    {
        $fileName = $this->configProvider->getFilename($storeId);

        if ($type == FeedType::PREVIEW) {
            $fileName = str_replace('.xml', '-preview.xml', $fileName);
        }

        $fileName = $addTimeStamp
            ? str_replace('.xml', '-' . time() . '.xml', $fileName)
            : $fileName;

        return sprintf(
            $addTimeStamp ? '%s/sooqr/data/%s' : '%s/sooqr/%s',
            $this->directoryList->getPath(DirectoryList::MEDIA),
            $fileName
        );
    }

    /**
     * @param float $timeStart
     *
     * @return string
     */
    private function getTimeUsage(float $timeStart): string
    {
        $time = round((microtime(true) - $timeStart));
        if ($time > 120) {
            $time = round($time / 60, 1) . ' ' . __('minute(s)')->render();
        } else {
            $time = round($time) . ' ' . __('second(s)')->render();
        }

        return $time;
    }
}
