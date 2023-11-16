<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Generate;

use Magento\Framework\Exception\LocalizedException;
use Magmodules\Sooqr\Model\Config\Source\FeedExecBy;
use Magmodules\Sooqr\Model\Config\Source\FeedType;

/**
 * Feed generate repository interface
 */
interface RepositoryInterface
{

    /**
     * Generate feed and write to file
     *
     * @param int $storeId
     * @param int $type
     * @param int $executedBy
     * @throws LocalizedException
     * @return array
     */
    public function execute(
        int $storeId,
        int $type = FeedType::FULL,
        int $executedBy = FeedExecBy::MANUAL
    ): array;
}
