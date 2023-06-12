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
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magmodules\Sooqr\Api\Generate\RepositoryInterface as GenerateFeedRepository;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Sooqr\Model\Config\Source\FeedType;

/**
 * Feed preview controller
 */
class Preview extends Action
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magmodules_Sooqr::config';

    /**
     * @var GenerateFeedRepository
     */
    private $generateGenerateFeedRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var DriverFile
     */
    private $driver;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * Preview constructor.
     *
     * @param Action\Context $context
     * @param LogRepository $logRepository
     * @param GenerateFeedRepository $generateGenerateFeedRepository
     * @param DriverFile $driver
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        LogRepository $logRepository,
        GenerateFeedRepository $generateGenerateFeedRepository,
        DriverFile $driver,
        RedirectInterface $redirect
    ) {
        $this->generateGenerateFeedRepository = $generateGenerateFeedRepository;
        $this->logRepository = $logRepository;
        $this->driver = $driver;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * Execute function for preview of the Sooqr feed in admin.
     */
    public function execute()
    {
        try {
            $result = $this->generateGenerateFeedRepository->execute(
                (int)$this->getRequest()->getParam('store_id'),
                FeedType::PREVIEW
            );
            $this->getResponse()->setHeader('Content-type', 'text/xml');
            return $this->getResponse()->setBody(
                $this->driver->fileGetContents($result['path'])
            );
        } catch (\Exception $e) {
            $this->logRepository->addErrorLog('Generate', $e->getMessage());
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->redirect->getRefererUrl());
        return $resultRedirect;
    }
}
