<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DB\Adapter\AdapterInterface;
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

    private ConfigProvider $configProvider;
    private File $file;
    private ResourceConnection $resourceConnection;
    private LogRepository $logger;
    private DirectoryList $directoryList;

    /**
     * @param ConfigProvider $configProvider
     * @param File $file
     * @param LogRepository $logger
     * @param ResourceConnection $resourceConnection
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ConfigProvider $configProvider,
        File $file,
        LogRepository $logger,
        ResourceConnection $resourceConnection,
        DirectoryList $directoryList
    ) {
        $this->configProvider = $configProvider;
        $this->file = $file;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->directoryList = $directoryList;
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
        $filesToDelete = $this->getFilesToDelete($connection, $offset);

        $path = $this->directoryList->getPath(DirectoryList::MEDIA) . '/sooqr/data/';

        foreach ($filesToDelete as $filename) {
            $this->deleteFile($path, $filename);
        }

        $this->clearFileReferencesInDatabase($connection, $offset);

        $connection->update(
            $this->resourceConnection->getTableName('sooqr_feed'),
            ['filename' => null, 'webhook_url' => null],
            ['created_at < ?' => date("Y-m-d h:i:s", strtotime("-{$offset} days"))]
        );
    }

    /**
     * Clear file references in the database for outdated entries.
     *
     * @param AdapterInterface $connection
     * @param int $offset
     * @return void
     */
    private function clearFileReferencesInDatabase(AdapterInterface $connection, int $offset): void
    {
        $connection->update(
            $this->resourceConnection->getTableName('sooqr_feed'),
            ['filename' => null],
            ['created_at < ?' => $this->getPastDate($offset)]
        );
    }

    /**
     * Delete a file and log any errors.
     *
     * @param string $path
     * @param string $filename
     * @return void
     */
    private function deleteFile(string $path, string $filename): void
    {
        try {
            $filename = $path . $filename . '.xml';
            if ($this->file->isExists($filename)) {
                $this->file->deleteFile($filename);
            }
        } catch (FileSystemException $exception) {
            $this->logger->addDebugLog('cleanupFiles', $exception->getMessage());
        }
    }

    /**
     * Get files to delete based on the offset.
     *
     * @param AdapterInterface $connection
     * @param int $offset
     * @return array
     */
    private function getFilesToDelete(AdapterInterface $connection, int $offset): array
    {
        $tableName = $this->resourceConnection->getTableName('sooqr_feed');

        return $connection->fetchCol(
            $connection->select()
                ->from($tableName, ['filename'])
                ->where('created_at < ?', $this->getPastDate($offset))
                ->where('filename IS NOT NULL')
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

    /**
     * Get a formatted past date based on the offset.
     *
     * @param int $offset
     * @return string
     */
    private function getPastDate(int $offset): string
    {
        return date('Y-m-d H:i:s', strtotime("-{$offset} days"));
    }
}
