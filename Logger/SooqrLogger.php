<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Logger;

use Monolog\Logger;

class SooqrLogger extends Logger
{

    /**
     * @param $msg
     */
    public function addInfoLog($msg)
    {
        $this->addInfo($msg);
    }
}
