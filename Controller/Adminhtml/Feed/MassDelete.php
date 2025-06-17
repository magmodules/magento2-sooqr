<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Sooqr\Api\Feed\RepositoryInterface as FeedRepository;
use Magmodules\Sooqr\Model\Feed\CollectionFactory;
use Magmodules\Sooqr\Model\Feed\Collection as FeedCollection;

/**
 * Mass Delete Feed Controller
 */
class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'Magmodules_Sooqr::feed';

    private FeedRepository $feedRepository;
    private CollectionFactory $collectionFactory;
    private Filter $filter;

    /**
     * Constructor.
     *
     * @param Action\Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param FeedRepository $feedRepository
     */
    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        FeedRepository $feedRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->feedRepository = $feedRepository;
    }

    /**
     * Execute function to mass delete Google Shopping feeds in admin.
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $deletedCount = 0;
        foreach ($this->getCollection() as $feed) {
            try {
                $this->feedRepository->deleteById((int)$feed->getId());
                $deletedCount++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Error deleting feed ID %1: %2', $feed->getId(), $e->getMessage())
                );
            }
        }

        if ($deletedCount > 0) {
            $this->messageManager->addSuccessMessage(__('Successfully deleted %1 feed(s).', $deletedCount));
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Get selected collection
     *
     * @return FeedCollection $collection
     * @throws LocalizedException
     */
    private function getCollection(): FeedCollection
    {
        if ($selected = $this->getRequest()->getParam('selected')) {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter(
                    'entity_id',
                    ['in' => $selected]
                );
        } else {
            /** @var FeedCollection $collection */
            $collection = $this->collectionFactory->create();
        }

        return $collection;
    }
}
