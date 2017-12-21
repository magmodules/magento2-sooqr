<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magento\Framework\Locale\Resolver;

/**
 * Class Init
 *
 * @package Magmodules\Sooqr\Block
 */
class Init extends Template
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
     * @var Resolver
     */
    private $localeResolver;

    /**
     * Init constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $generalHelper
     * @param Resolver      $localeResolver
     * @param array         $data
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        Resolver $localeResolver,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $context->getStoreManager();
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->localeResolver = $localeResolver;
        $this->generalHelper = $generalHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return bool|mixed
     */
    public function getFrontendEnabled()
    {
        return $this->generalHelper->getFrontendEnabled($this->storeId);
    }

    /**
     * @return array
     */
    public function getSooqrOptions()
    {
        $accountId = $this->generalHelper->getAccountId($this->storeId);
        $options = ['account' => $accountId, 'fieldId' => 'search'];

        if ($parent = $this->generalHelper->getParent($this->storeId)) {
            $options['containerParent'] = $parent;
        }

        if ($version = $this->generalHelper->getVersion($this->storeId)) {
            $options['version'] = $version;
        }

        return $options;
    }

    /**
     * @return string
     */
    public function getSooqrLanguage()
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * @return mixed
     */
    public function getSooqrJavascript()
    {
        if ($customJs = $this->generalHelper->getCustomJs($this->storeId)) {
            return $customJs;
        }
    }

    /**
     * @return mixed
     */
    public function isTrackingEnabled()
    {
        return $this->generalHelper->getStatistics($this->storeId);
    }

    /**
     * @return string
     */
    public function getSooqrScriptUri()
    {
        if ($statging = $this->generalHelper->getStaging($this->storeId)) {
            return 'static.staging.sooqr.com/sooqr.js';
        }
        return 'static.sooqr.com/sooqr.js';
    }

    /**
     * @return mixed
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }
}
