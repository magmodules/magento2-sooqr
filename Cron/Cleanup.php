<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

/**
 * Cleanup generation cron
 */
class Cleanup
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var File
     */
    private $file;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var LogRepository
     */
    private $logger;

    /**
     * @param ConfigProvider $configProvider
     * @param File $file
     * @param LogRepository $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ConfigProvider $configProvider,
        File $file,
        LogRepository $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->configProvider = $configProvider;
        $this->file = $file;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute function for cleanup feed files by cron.
     */
    public function execute()
    {
        $offset = $this->configProvider->getCleanupOffset();

        $this->removeFiles($offset);
        $this->removeEntries($offset + 14);
    }

    /**
     * Remove generated XML files older than offset
     *
     * @param $offset
     * @return void
     */
    private function removeFiles($offset)
    {
        $connection = $this->resourceConnection->getConnection();
        $selectFiles = $connection->select()->from(
            $this->resourceConnection->getTableName('sooqr_feed'),
            ['filename']
        )->where(
            'created_at < ?',
            date("Y-m-d h:i:s", strtotime("-{$offset} days"))
        )->where(
            'filename IS NOT NULL'
        );

        foreach ($connection->fetchCol($selectFiles) as $filename) {
            try {
                if ($this->file->isExists($filename)) {
                    $this->file->deleteFile($filename);
                }
            } catch (FileSystemException $exception) {
                $this->logger->addDebugLog('removeFiles', $exception->getMessage());
            }
        }

        $connection->update(
            $this->resourceConnection->getTableName('sooqr_feed'),
            ['filename' => null, 'webhook_url' => null],
            ['created_at < ?' => date("Y-m-d h:i:s", strtotime("-{$offset} days"))]
        );
    }

    /**
     * Remove database entries from 'sooqr_feed' table older than offset + 14-days
     *
     * @param $offset
     * @return void
     */
    private function removeEntries($offset)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            $this->resourceConnection->getTableName('sooqr_feed'),
            ['created_at < ?' => date("Y-m-d h:i:s", strtotime("-{$offset} days"))]
        );
    }
}
