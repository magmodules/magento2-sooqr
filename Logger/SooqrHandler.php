<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class SooqrHandler
 *
 * @package Magmodules\Sooqr\Logger
 */
class SooqrHandler extends Base
{

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
    /**
     * @var string
     */
    protected $fileName = '/var/log/sooqr.log';
}
