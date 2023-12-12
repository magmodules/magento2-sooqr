<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Cron;

use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Generate\RepositoryInterface as GenerateFeedRepository;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Sooqr\Model\Config\Source\FeedType;
use Magmodules\Sooqr\Model\Config\Source\FeedExecBy;

/**
 * Delta generation cron
 */
class Delta
{

    /**
     * @var GenerateFeedRepository
     */
    private $generateFeedRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * Feed constructor.
     *
     * @param GenerateFeedRepository $generateFeedRepository
     * @param LogRepository $logRepository
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        GenerateFeedRepository $generateFeedRepository,
        LogRepository $logRepository,
        ConfigProvider $configProvider
    ) {
        $this->generateFeedRepository = $generateFeedRepository;
        $this->logRepository = $logRepository;
        $this->configProvider = $configProvider;
    }

    /**
     * Execute function for generation of the Sooqr feed in cron.
     */
    public function execute()
    {
        foreach ($this->configProvider->getAllEnabledStoreIds() as $storeId) {
            try {
                $this->generateFeedRepository->execute(
                    (int)$storeId,
                    FeedType::DELTA,
                    FeedExecBy::CRON
                );
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('Generate', $e->getMessage());
            }
        }

        $this->configProvider->setCategoryChangedFlag(false);
    }
}
