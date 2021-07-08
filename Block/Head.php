<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

/**
 * Class Head
 *
 */
class Head extends Template
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var int
     */
    private $storeId;

    /**
     * Head constructor.
     *
     * @param Context $context
     * @param GeneralHelper $generalHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $context->getStoreManager();
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->generalHelper = $generalHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function getHeadDataEnabled()
    {
        return $this->generalHelper->getHeadDataEnabled($this->storeId);
    }
}