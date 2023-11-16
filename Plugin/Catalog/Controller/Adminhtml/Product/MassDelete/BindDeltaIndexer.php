<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Plugin\Catalog\Controller\Adminhtml\Product\MassDelete;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Controller\Adminhtml\Product\MassDelete;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Ui\Component\MassAction\Filter;
use Magmodules\Sooqr\Model\Delta\Indexer;

/**
 * Binds our delta indexer calls to the mass delete controller
 */
class BindDeltaIndexer
{
    /**
     * @var Indexer
     */
    private $deltaIndexer;
    /**
     * @var Filter
     */
    private $filter;
    /**
     * @var ProductCollectionFactory
     */
    private $collectionFactory;
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param Indexer $deltaIndexer
     * @param Filter $filter
     * @param ProductCollectionFactory $collectionFactory
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        Indexer $deltaIndexer,
        Filter $filter,
        ProductCollectionFactory $collectionFactory,
        IndexerRegistry $indexerRegistry
    ) {
        $this->deltaIndexer = $deltaIndexer;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Execute delta indexation process for deleted products
     *
     * @param MassDelete $subject
     * @param callable $proceed
     * @return Redirect
     * @throws LocalizedException
     */
    public function aroundExecute(MassDelete $subject, callable $proceed)
    {
        $result = $proceed();

        if (!$this->indexerRegistry->get('sooqr_delta')->isScheduled()) {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $this->deltaIndexer->execute($collection->getAllIds());
        }

        return $result;
    }
}
