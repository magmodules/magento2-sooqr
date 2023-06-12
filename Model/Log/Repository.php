<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Log;

use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepositoryInterface;
use Magmodules\Sooqr\Logger\DebugLogger;
use Magmodules\Sooqr\Logger\ErrorLogger;

/**
 * Logs repository class
 */
class Repository implements LogRepositoryInterface
{

    /**
     * @var DebugLogger
     */
    private $debugLogger;
    /**
     * @var ErrorLogger
     */
    private $errorLogger;
    /**
     * @var ConfigRepositoryInterface
     */
    private $configRepository;

    /**
     * Repository constructor.
     *
     * @param DebugLogger $debugLogger
     * @param ErrorLogger $errorLogger
     * @param ConfigRepositoryInterface $configRepository
     */
    public function __construct(
        DebugLogger $debugLogger,
        ErrorLogger $errorLogger,
        ConfigRepositoryInterface $configRepository
    ) {
        $this->debugLogger = $debugLogger;
        $this->errorLogger = $errorLogger;
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritDoc
     */
    public function addErrorLog(string $type, $data): void
    {
        $this->errorLogger->addLog($type, $data);
    }

    /**
     * @inheritDoc
     */
    public function addDebugLog(string $type, $data): void
    {
        if ($this->configRepository->logDebug()) {
            $this->debugLogger->addLog($type, $data);
        }
    }
}
