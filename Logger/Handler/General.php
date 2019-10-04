<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Logger\Monolog;

/**
 * Class General
 *
 * @package Magmodules\Sooqr\Logger\Handler
 */
class General extends Base
{

    /**
     * @var string
     */
    protected $fileName = '/var/log/sooqr/general.log';

    /**
     * @var int
     */
    protected $loggerType = Monolog::DEBUG;

}