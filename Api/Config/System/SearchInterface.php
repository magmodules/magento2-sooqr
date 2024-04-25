<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Config\System;

/**
 * Search Config group interface
 */
interface SearchInterface
{

    /** Frontend Group */
    public const XML_PATH_SEARCH_ENABLE = 'sooqr_search/frontend/enable';
    public const XML_PATH_SEARCH_LOADER = 'sooqr_search/frontend/loader';
    public const XML_PATH_SEARCH_STATISTICS = 'sooqr_search/frontend/statistics';
    public const XML_PATH_SEARCH_PARENT = 'sooqr_search/frontend/advanced_parent';
    public const XML_PATH_SEARCH_DEBUG = 'sooqr_search/frontend/advanced_debug';
    public const XML_PATH_SEARCH_VERSION = 'sooqr_search/frontend/advanced_version';
    public const XML_PATH_SEARCH_CUSTOM_JS = 'sooqr_search/frontend/advanced_custom_js';
    public const XML_PATH_ADD_TO_CART = 'sooqr_search/frontend/add_to_cart';
    public const XML_PATH_ADD_TO_CART_AJAX = 'sooqr_search/frontend/add_to_cart_ajax';
    public const XML_PATH_ADD_TO_WISHLIST = 'sooqr_search/frontend/add_to_wishlist';
    public const XML_PATH_ADD_TO_COMPARE = 'sooqr_search/frontend/add_to_compare';

    /**
     * @return bool
     */
    public function isSearchEnabled(): bool;

    /***
     * @return bool
     */
    public function statisticsEnabled(): bool;

    /**
     * @return ?string
     */
    public function getParent(): ?string;

    /***
     * @return bool
     */
    public function debugEnabled(): bool;

    /**
     * @return bool
     */
    public function addToCartController(): bool;

    /**
     * @return bool
     */
    public function isAjaxAddToCartEnabled(): bool;

    /**
     * @return bool
     */
    public function addToWishlistController(): bool;

    /**
     * @return bool
     */
    public function addToCompareController(): bool;

    /**
     * @return string
     */
    public function getDesignVersion(): string;

    /**
     * @return string
     */
    public function getLoaderType(): string;

    /**
     * @return array
     */
    public function getCustomJs(): array;
}
