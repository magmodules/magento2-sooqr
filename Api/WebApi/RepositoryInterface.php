<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Api\WebApi;

/**
 * WebApi repository interface
 */
interface RepositoryInterface
{

    /**
     * @param string $code
     * @return string[]
     */
    public function getFeed(string $code): array;
}
