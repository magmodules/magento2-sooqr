<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\Cms;

/**
 * CMS repository interface
 */
interface RepositoryInterface
{

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getCmsPages(int $storeId): array;
}
