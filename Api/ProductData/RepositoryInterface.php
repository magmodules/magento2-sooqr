<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\ProductData;

use Magmodules\Sooqr\Model\Config\Source\FeedType;

/**
 * Product data repository interface
 */
interface RepositoryInterface
{

    /**
     * Get formatted product data
     *
     * @param int $storeId
     * @param array|null $entityIds
     * @param int $type
     * @return array
     */
    public function getProductData(int $storeId = 0, ?array $entityIds = null, int $type = FeedType::FULL): array;

    /**
     * Collect all used product attributes
     *
     * @param int $storeId
     * @return array
     */
    public function getProductAttributes(int $storeId = 0): array;
}
