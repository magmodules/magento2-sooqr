<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Delta;

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magmodules\Sooqr\Api\Log\RepositoryInterface;
use Magmodules\Sooqr\Service\Delta\Set as SetDelta;

/**
 * Delta Indexer Class
 */
class Indexer implements IndexerActionInterface, MviewActionInterface
{

    /**
     * @var SetDelta
     */
    private $setDelta;
    /**
     * @var RepositoryInterface
     */
    private $logger;

    /**
     * Indexer constructor.
     * @param SetDelta $setDelta
     * @param RepositoryInterface $logger
     */
    public function __construct(
        SetDelta $setDelta,
        RepositoryInterface $logger
    ) {
        $this->setDelta = $setDelta;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function executeFull()
    {
        $this->logger->addDebugLog('Indexer execute full', 'all');
        $this->setDelta->execute([]);
    }

    /**
     * @inheritdoc
     */
    public function execute($ids)
    {
        $this->logger->addDebugLog('Indexer execute', $ids);
        $this->setDelta->execute($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $ids)
    {
        $this->logger->addDebugLog('Indexer execute list', $ids);
        $this->setDelta->execute($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeRow($id)
    {
        $this->logger->addDebugLog('Indexer execute row', $id);
        $this->setDelta->execute([$id]);
    }
}
