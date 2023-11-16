<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Frontend Source Option Source model
 */
class Frontend implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '4', 'label' => __('Version 4 (Responsive)')],
        ];
    }
}
