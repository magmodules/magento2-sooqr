<?php
/**
 * Copyright © Sooqr. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Head;

use Magento\Checkout\Model\SessionFactory as Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Get
 * Ajax controller to get queued events
 */
class Get extends Action 
{

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * Get constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $headData = $this->checkoutSession->create()->getSooqrHead();
        $html = '<script type="text/javascript">
        var sooqrUenc = "%s";
        var sooqrFormKey = "%s";
    </script>';
        $html = sprintf($html,
            $headData['uenc'],
            $headData['form_key']
        );
        $result->setData($html);
        return $result;
    }
}