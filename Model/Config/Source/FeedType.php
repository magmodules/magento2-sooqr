<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Log Type Source Option Source model
 */
class FeedType implements OptionSourceInterface
{

    public const PREVIEW = 1;
    public const DELTA = 2;
    public const FULL = 3;

    public const TYPES = [
        self::PREVIEW => 'preview',
        self::DELTA => 'delta',
        self::FULL => 'full',
    ];

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::PREVIEW, 'label' => __('Preview')],
            ['value' => self::DELTA, 'label' => __('Delta')],
            ['value' => self::FULL, 'label' => __('Full')],
        ];
    }
}
