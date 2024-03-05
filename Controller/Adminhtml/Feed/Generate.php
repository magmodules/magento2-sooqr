<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Generate\RepositoryInterface as GenerateFeedRepository;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Sooqr\Model\Config\Source\FeedType;

/**
 * Feed generation controller
 */
class Generate extends Action
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magmodules_Sooqr::feed';

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
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * Generate constructor.
     *
     * @param Action\Context $context
     * @param GenerateFeedRepository $generateFeedRepository
     * @param ConfigProvider $configProvider
     * @param LogRepository $logRepository
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        GenerateFeedRepository $generateFeedRepository,
        ConfigProvider $configProvider,
        LogRepository $logRepository,
        RedirectInterface $redirect
    ) {
        $this->generateFeedRepository = $generateFeedRepository;
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * Execute function for generation of the Sooqr feed in admin.
     */
    public function execute(): Redirect
    {
        $storeIds = $this->getStoreIds();
        if (empty($storeIds)) {
            $this->messageManager->addErrorMessage(__('No stores found to generate feed'));
        }

        $type = $this->getRequest()->getParam('type', null);
        foreach ($storeIds as $storeId) {
            try {
                $result = $this->generateFeedRepository->execute(
                    (int)$storeId,
                    $type = $this->getRequest()->getParam('type') ? (int)$type : FeedType::FULL
                );
                $this->messageManager->addSuccessMessage($result['message']);
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('Generate', $e->getMessage());
                $this->messageManager->addErrorMessage(__($e->getMessage()));
            }
        }

        $this->configProvider->setCategoryChangedFlag(false);

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->redirect->getRefererUrl());
        return $resultRedirect;
    }

    /**
     * @return array
     */
    private function getStoreIds(): array
    {
        return $this->getRequest()->getParam('store_id')
            ? [$this->getRequest()->getParam('store_id')]
            : $this->configProvider->getAllEnabledStoreIds();
    }
}
