<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Controller\Adminhtml\Actions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magmodules\Sooqr\Helper\Feed as FeedHelper;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Download extends Action
{

    private $feedHelper;
    private $fileFactory;
    private $baseDir = null;

    /**
     * Download constructor.
     *
     * @param Context       $context
     * @param FileFactory   $fileFactory
     * @param FeedHelper    $feedHelper
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        FeedHelper $feedHelper,
        DirectoryList $directoryList
    ) {
        $this->feedHelper = $feedHelper;
        $this->fileFactory = $fileFactory;
        $this->baseDir = $directoryList->getPath(DirectoryList::ROOT);
        parent::__construct($context);
    }

    /**
     * Execute function for download of the Sooqr feed in admin.
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $feed = $this->feedHelper->getFeedLocation($storeId);
        if (!empty($feed['full_path']) && file_exists($feed['full_path'])) {
            $response = $this->fileFactory->create(
                basename($feed['full_path']),
                file_get_contents($feed['full_path']),
                DirectoryList::ROOT,
                'application/octet-stream',
                null
            );
            return $response;
        } else {
            $this->messageManager->addError(__('File not found, please generate new feed.'));
            $this->_redirect('adminhtml/system_config/edit/section/magmodules_sooqr');
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magmodules_Sooqr::config');
    }
}
