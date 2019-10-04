<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Logger;

/**
 * Interface ValidationLoggerInterface
 *
 * @package Magmodules\Sooqr\Logger
 */
interface ValidationLoggerInterface
{

    /**
     * @param string $type
     * @param $data
     * @return void
     */
    public function add($type, $data);
}
