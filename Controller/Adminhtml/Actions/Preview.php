<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Controller\Adminhtml\Actions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magmodules\Sooqr\Model\Generate as GenerateModel;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

class Preview extends Action
{

    private $generate;
    private $generalHelper;

    /**
     * Preview constructor.
     * @param Context $context
     * @param GeneralHelper $generalHelper
     * @param GenerateModel $generateModel
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        GenerateModel $generateModel
    ) {
        $this->generate = $generateModel;
        $this->generalHelper = $generalHelper;
        parent::__construct($context);
    }

    /**
     * Execute function for preview of the Sooqr feed in admin.
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        if (!$this->generalHelper->getEnabled($storeId)) {
            $errorMsg = __('Please enable the extension before generating the feed.');
            $this->messageManager->addError($errorMsg);
            $this->_redirect('adminhtml/system_config/edit/section/magmodules_sooqr');
        } else {
            $page = $this->getRequest()->getParam('page', 1);
            $productId = $this->getRequest()->getParam('pid', []);
            if ($result = $this->generate->generateByStore($storeId, ' preview', $productId, $page)) {
                $this->getResponse()->setHeader('Content-type', 'text/xml');
                $this->getResponse()->setBody(file_get_contents($result['path']));
            } else {
                $errorMsg = __('Unkown error.');
                $this->messageManager->addError($errorMsg);
                $this->_redirect('adminhtml/system_config/edit/section/magmodules_sooqr');
            }
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
