<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Data\Form\FormKey;

use Magmodules\Sooqr\Helper\General as GeneralHelper;

/**
 * Class Head
 *
 * @package Magmodules\Sooqr\Block
 */
class Head extends Template
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var int
     */
    private $storeId;
    /**
     * @var FormKey
     */
    private $formkey;
    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * Head constructor.
     *
     * @param Context          $context
     * @param GeneralHelper    $generalHelper
     * @param EncoderInterface $urlEncoder
     * @param FormKey          $formkey
     * @param array            $data
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        EncoderInterface $urlEncoder,
        FormKey $formkey,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $context->getStoreManager();
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->generalHelper = $generalHelper;
        $this->urlEncoder = $urlEncoder;
        $this->formkey = $formkey;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function getHeadDataEnabled()
    {
        return $this->generalHelper->getHeadDataEnabled($this->storeId);
    }

    /**
     * @return string
     */
    public function getUenc()
    {
        return $this->urlEncoder->encode($this->getCurrentUrl());
    }

    /**
     * @return mixed
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getUrl('*/*/*', ['_use_rewrite' => true, '_current' => true]);
    }

    /**
     * @return mixed
     */
    public function getFormKey()
    {
        return $this->formkey->getFormKey();
    }
}
