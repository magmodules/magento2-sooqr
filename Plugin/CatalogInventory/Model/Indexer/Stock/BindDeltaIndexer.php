<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Plugin\CatalogInventory\Model\Indexer\Stock;

use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magmodules\Sooqr\Model\Delta\Indexer;

/**
 * Binds our delta indexer calls to the appropriate stock indexer ones
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
     * @return mixed
     */
    public function beforeExecute(ActionInterface $subject, $ids)
    {
        if (!$this->indexerRegistry->get('sooqr_delta')->isScheduled()) {
            $this->deltaIndexer->execute($ids);
        }

        return null;
    }

    /**
     * Binding executeList method
     *
     * @param ActionInterface $subject
     * @param array $ids
     * @return mixed
     */
    public function beforeExecuteList(ActionInterface $subject, array $ids)
    {
        if (!$this->indexerRegistry->get('sooqr_delta')->isScheduled()) {
            $this->deltaIndexer->executeList($ids);
        }

        return null;
    }

    /**
     * Binding executeRow method
     *
     * @param ActionInterface $subject
     * @param int $id
     * @return mixed
     */
    public function beforeExecuteRow(ActionInterface $subject, $id)
    {
        if (!$this->indexerRegistry->get('sooqr_delta')->isScheduled()) {
            $this->deltaIndexer->executeRow($id);
        }

        return null;
    }
}
