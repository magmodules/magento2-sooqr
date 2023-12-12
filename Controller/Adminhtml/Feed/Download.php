<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Io\File;
use Magmodules\Sooqr\Api\Feed\RepositoryInterface as FeedRepository;

/**
 * Class Download
 */
class Download extends Action
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magmodules_Sooqr::feed';

    /**
     * @var FileFactory
     */
    private $fileFactory;
    /**
     * @var File
     */
    private $ioFilesystem;
    /**
     * @var FeedRepository
     */
    private $feedRepository;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * Download constructor.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param File $ioFilesystem
     * @param FeedRepository $feedRepository
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        File $ioFilesystem,
        FeedRepository $feedRepository,
        RedirectInterface $redirect
    ) {
        $this->fileFactory = $fileFactory;
        $this->ioFilesystem = $ioFilesystem;
        $this->feedRepository = $feedRepository;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * Execute function for download of the feed file.
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        try {
            $feed = $this->feedRepository->get($id);
            $fileInfo = $this->ioFilesystem->getPathInfo($feed->getFilename());
            return $this->fileFactory->create(
                $fileInfo['basename'],
                [
                    'type' => 'filename',
                    'value' => $feed->getFilename()
                ]
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->redirect->getRefererUrl());
            return $resultRedirect;
        }
    }
}
