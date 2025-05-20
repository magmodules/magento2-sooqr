<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Feed;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Sooqr\Api\Feed\Data\DataInterface;

/**
 * Feed resource model
 */
class ResourceModel extends AbstractDb
{

    /**
     * Main table name
     */
    public const ENTITY_TABLE = 'sooqr_feed';

    /**
     * Main column name
     */
    public const PRIMARY = 'entity_id';

    private File $file;
    private DirectoryList $directoryList;
    private LogRepository $logRepository;

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(static::ENTITY_TABLE, static::PRIMARY);
    }

    public function __construct(
        Context $context,
        File $file,
        LogRepository $logRepository,
        DirectoryList $directoryList,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->file = $file;
        $this->logRepository = $logRepository;
        $this->directoryList = $directoryList;
    }

    /**
     * Perform actions before object delete
     *
     * @param AbstractModel $object
     * @return AbstractDb
     */
    protected function _beforeDelete(AbstractModel $object): AbstractDb
    {
        $filename = $object->getData(DataInterface::FILENAME);

        if ($filename) {
            try {
                $path = $this->directoryList->getPath(DirectoryList::MEDIA) . '/sooqr/data/';
                $this->file->isExists($path . $filename . '.xml');
                $this->file->deleteFile($path . $filename . '.xml');
            } catch (FileSystemException $e) {
                $this->logRepository->addErrorLog('Delete Feed', $e->getMessage());
            }
        }

        return parent::_beforeDelete($object);
    }

    /**
     * Check is entity exists
     *
     * @param int $primaryId
     * @return bool
     */
    public function isExists(int $primaryId): bool
    {
        $condition = sprintf('%s = :%s', static::PRIMARY, static::PRIMARY);
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getTable(static::ENTITY_TABLE),
            static::PRIMARY
        )->where($condition);
        $bind = [sprintf(':%s', static::PRIMARY) => $primaryId];
        return (bool)$connection->fetchOne($select, $bind);
    }
}
