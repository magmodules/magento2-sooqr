<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config\System;

use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigInterface;
use Magmodules\Sooqr\Api\Config\System\SearchInterface;

/**
 * Search provider class
 */
class SearchRepository extends BaseConfigRepository implements SearchInterface
{

    /**
     * @inheritDoc
     */
    public function statisticsEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEARCH_STATISTICS);
    }

    /**
     * @inheritDoc
     */
    public function getParent(): ?string
    {
        return $this->getStoreValue(self::XML_PATH_SEARCH_PARENT);
    }

    /**
     * @inheritDoc
     */
    public function debugEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEARCH_DEBUG);
    }

    /**
     * @inheritDoc
     */
    public function getDesignVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_SEARCH_VERSION);
    }

    /**
     * @inheritDoc
     */
    public function getCustomJs(): array
    {
        return $this->getStoreValueArray(self::XML_PATH_SEARCH_CUSTOM_JS);
    }

    /**
     * @inheritDoc
     */
    public function addToWishlistController(): bool
    {
        return $this->isSearchEnabled() && $this->isSetFlag(self::XML_PATH_ADD_TO_WISHLIST);
    }

    /**
     * @inheritDoc
     */
    public function isSearchEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEARCH_ENABLE)
            && $this->isSetFlag(ConfigInterface::XML_PATH_EXTENSION_ENABLE);
    }

    /**
     * @inheritDoc
     */
    public function isAjaxAddToCartEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_ADD_TO_CART_AJAX, $storeId)
            && $this->addToCartController();
    }

    /**
     * @inheritDoc
     */
    public function addToCartController(): bool
    {
        return $this->isSearchEnabled() && $this->isSetFlag(self::XML_PATH_ADD_TO_CART);
    }

    /**
     * @inheritDoc
     */
    public function getLoaderType(): string
    {
        return $this->getStoreValue(self::XML_PATH_SEARCH_LOADER) ?? 'default';
    }
}
