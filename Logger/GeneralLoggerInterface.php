<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Logger;

/**
 * Interface GeneralLoggerInterface
 *
 * @package Magmodules\Sooqr\Logger
 */
interface GeneralLoggerInterface
{

    /**
     * @param string $type
     * @param $data
     * @return void
     */
    public function add($type, $data);

}