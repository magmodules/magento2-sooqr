<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

/**
 * Class Init
 *
 * @package Magmodules\Sooqr\Block
 */
class Init extends Template
{

    const SOOQR_SCRIPT_URL = 'static.sooqr.com/sooqr.js';
    const SOOQR_CUSTOM_SCRIPT_URL = 'static.sooqr.com/custom/%s/snippet.js';

    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Resolver
     */
    private $localeResolver;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Init constructor.
     *
     * @param Context $context
     * @param GeneralHelper $generalHelper
     * @param Resolver $localeResolver
     * @param array $data
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        Resolver $localeResolver,
        SerializerInterface $serializer,
        array $data = []
    ) {
        $this->storeManager = $context->getStoreManager();
        $this->localeResolver = $localeResolver;
        $this->generalHelper = $generalHelper;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * @return bool|mixed
     */
    public function getFrontendEnabled()
    {
        return $this->generalHelper->getFrontendEnabled();
    }

    /**
     * Check if ajax based AddToCart is enabled.
     *
     * @return bool
     */
    public function isAjaxAddToCartEnabled(): bool
    {
        return $this->generalHelper->isAjaxAddToCartEnabled();
    }

    /**
     * @return string
     */
    public function getSooqrOptions(): string
    {
        $accountId = $this->generalHelper->getAccountId();
        $options = ['account' => $accountId, 'fieldId' => 'search'];

        if ($parent = $this->generalHelper->getParent()) {
            $options['containerParent'] = $parent;
        }

        if ($version = $this->generalHelper->getVersion()) {
            $options['version'] = $version;
        }

        return $this->serializer->serialize($options);
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
        if ($customJs = $this->generalHelper->getCustomJs()) {
            return $customJs;
        }
    }

    /**
     * @return mixed
     */
    public function isTrackingEnabled()
    {
        return $this->generalHelper->getStatistics();
    }

    /**
     * @return string
     */
    public function getSooqrScriptUri(): string
    {
        if ($this->getLoaderType() == 'custom') {
            return sprintf(
                self::SOOQR_CUSTOM_SCRIPT_URL,
                $this->getCleanAccountId()
            );
        }

        return self::SOOQR_SCRIPT_URL;
    }

    /**
     * @return string
     */
    public function getLoaderType(): string
    {
        return $this->generalHelper->getLoaderType();
    }

    /**
     * @return ?int
     */
    public function getCleanAccountId(): ?int
    {
        $accountId = trim((string)$this->generalHelper->getAccountId());
        if ($accountId && preg_match('/-(.*?)-/', $accountId, $match) == 1) {
            return (int)$match[1];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getStoreCode(): string
    {
        try {
            return $this->storeManager->getStore()->getCode();
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }
}
