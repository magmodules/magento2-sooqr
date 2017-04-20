<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Controller\Adminhtml\Actions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magmodules\Sooqr\Helper\Feed as FeedHelper;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Download extends Action
{

    private $feed;
    private $resultRawFactory;
    private $fileFactory;

    /**
     * Download constructor.
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param FileFactory $fileFactory
     * @param FeedHelper $feed
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FileFactory $fileFactory,
        FeedHelper $feed
    ) {
        $this->feed = $feed;
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for download of the Google Shopping feed in admin.
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $feed = $this->feed->getFeedLocation($storeId);
        if (!empty($feed['path'])) {
            $this->fileFactory->create(
                $feed['path'],
                null,
                DirectoryList::ROOT,
                'application/octet-stream',
                null
            );
            $resultRaw = $this->resultRawFactory->create();
            $resultRaw->setContents(file_get_contents($feed['path']));
            return $resultRaw;
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
