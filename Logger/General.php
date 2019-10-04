<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Logger;

use Monolog\Logger;

/**
 * Class General
 *
 * @package Magmodules\Sooqr\Logger
 */
class General extends Logger implements GeneralLoggerInterface
{

    /**
     * {@inheritDoc}
     */
    public function add($type, $data)
    {
        if (is_array($data) || is_object($data)) {
            $this->addInfo($type . ':' . implode(PHP_EOL, $data));
        } else {
            $this->addInfo($type . ':' .  $data);
        }
    }
}