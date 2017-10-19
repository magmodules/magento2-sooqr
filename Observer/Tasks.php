<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Sooqr\Helper\Config as ConfigHelper;

class Tasks implements ObserverInterface
{

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * Tasks constructor.
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $section = $observer->getRequest()->getParam('section');
        if ($section == 'magmodules_sooqr') {
            $this->configHelper->run();
        }
    }
}
