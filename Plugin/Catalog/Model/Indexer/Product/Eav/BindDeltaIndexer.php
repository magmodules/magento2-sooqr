<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Plugin\Catalog\Model\Indexer\Product\Eav;

use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magmodules\Sooqr\Model\Delta\Indexer;

/**
 * Binds our delta indexer calls to the appropriate EAV indexer ones
 */
class BindDeltaIndexer
{
    /**
     * @var Indexer
     */
    private $deltaIndexer;
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * BindDeltaIndexer constructor.
     *
     * @param Indexer $deltaIndexer
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        Indexer $deltaIndexer,
        IndexerRegistry $indexerRegistry
    ) {
        $this->deltaIndexer = $deltaIndexer;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Binding execute method
     *
     * @param ActionInterface $subject
     * @param array $ids
     * @return void
     *
     */
    public function beforeExecute(ActionInterface $subject, $ids)
    {
        if (!$this->indexerRegistry->get('sooqr_delta')->isScheduled()) {
            $this->deltaIndexer->execute($ids);
        }
    }

    /**
     * Binding executeList method
     *
     * @param ActionInterface $subject
     * @param array $ids
     * @return void
     *
     */
    public function beforeExecuteList(ActionInterface $subject, array $ids)
    {
        if (!$this->indexerRegistry->get('sooqr_delta')->isScheduled()) {
            $this->deltaIndexer->executeList($ids);
        }
    }

    /**
     * Binding executeRow method
     *
     * @param ActionInterface $subject
     * @param int $id
     * @return void
     *
     */
    public function beforeExecuteRow(ActionInterface $subject, $id)
    {
        if (!$this->indexerRegistry->get('sooqr_delta')->isScheduled()) {
            $this->deltaIndexer->executeRow($id);
        }
    }
}
