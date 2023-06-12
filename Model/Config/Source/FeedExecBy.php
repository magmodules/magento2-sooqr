<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Feed Execution Source Option Source model
 */
class FeedExecBy implements OptionSourceInterface
{

    public const MANUAL = 1;
    public const CLI = 2;
    public const CRON = 3;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MANUAL, 'label' => __('Manual')],
            ['value' => self::CLI, 'label' => __('Command Line')],
            ['value' => self::CRON, 'label' => __('Cron')],
        ];
    }
}
