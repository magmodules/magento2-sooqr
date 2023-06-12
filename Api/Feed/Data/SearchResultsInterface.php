<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Feed\Data;

use Magento\Framework\Api\SearchResultsInterface as FrameworkSearchResultsInterface;
use Magmodules\Sooqr\Api\Feed\Data\DataInterface as FeedData;

/**
 * Interface for Feed search results.
 * @api
 */
interface SearchResultsInterface extends FrameworkSearchResultsInterface
{

    /**
     * Gets feed log items.
     *
     * @return FeedData[]
     */
    public function getItems(): array;

    /**
     * Sets feed log items.
     *
     * @param FeedData[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
