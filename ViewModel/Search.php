<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\ViewModel;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Provides search data for search.phtml
 */
class Search implements ArgumentInterface
{

    public const SOOQR_SCRIPT_URL = 'static.sooqr.com/sooqr.js';
    public const SOOQR_CUSTOM_SCRIPT_URL = 'static.sooqr.com/custom/%s/snippet.js';

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var Resolver
     */
    private $localeResolver;
    /**
     * @var Json
     */
    private $json;

    /**
     * @param ConfigProvider $configProvider
     * @param Json $json
     * @param Resolver $localeResolver
     */
    public function __construct(
        ConfigProvider $configProvider,
        Resolver $localeResolver,
        Json $json
    ) {
        $this->localeResolver = $localeResolver;
        $this->configProvider = $configProvider;
        $this->json = $json;
    }

    /**
     * Check if Sooqr Search is enabled.
     *
     * @return bool
     */
    public function isSearchEnabled(): bool
    {
        return $this->configProvider->isSearchEnabled();
    }

    /**
     * Check if ajax based AddToCart is enabled.
     *
     * @return bool
     */
    public function isAjaxAddToCartEnabled(): bool
    {
        return $this->configProvider->isAjaxAddToCartEnabled();
    }

    /**
     * @return string
     */
    public function getSooqrOptions(): string
    {
        $options = [
            'account' => $this->configProvider->getCredentials()['account_id'],
            'fieldId' => ['search', 'search_mobile', 'search_sticky', 'search_sticky_mobile']
        ];

        if ($parent = $this->configProvider->getParent()) {
            $options['containerParent'] = $parent;
        }

        if ($version = $this->configProvider->getDesignVersion()) {
            $options['version'] = $version;
        }

        $options += $this->getCustomJs();
        return $this->json->serialize($options);
    }

    /**
     * @return string
     */
    public function getSooqrLanguage(): string
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * @return array
     */
    public function getCustomJs(): array
    {
        return $this->configProvider->getCustomJs();
    }

    /**
     * @return bool
     */
    public function isTrackingEnabled(): bool
    {
        return $this->configProvider->statisticsEnabled();
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

        return static::SOOQR_SCRIPT_URL;
    }

    /**
     * @return string
     */
    public function getLoaderType(): string
    {
        return $this->configProvider->getLoaderType();
    }

    /**
     * @return ?int
     */
    private function getCleanAccountId(): ?int
    {
        $accountId = trim((string)$this->configProvider->getCredentials()['account_id']);
        if ($accountId && preg_match('/-(.*?)-/', $accountId, $match) == 1) {
            return (int)$match[1];
        }

        return null;
    }
}
