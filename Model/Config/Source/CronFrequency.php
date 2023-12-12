<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CronFrequency implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => '0 0 * * *',
                'label' => __('Daily at 0:00')
            ],
            [
                'value' => '0 */6 * * *',
                'label' => __('Every 6 hours')
            ],
            [
                'value' => '0 */4 * * *',
                'label' => __('Every 4 hours')
            ],
            [
                'value' => '0 */2 * * *',
                'label' => __('Every 2 hours')
            ],
            [
                'value' => '0 * * * *',
                'label' => __('Every hour')
            ],
            [
                'value' => '*/5 * * * *',
                'label' => __('Every 5 minutes')
            ],
        ];
    }
}
