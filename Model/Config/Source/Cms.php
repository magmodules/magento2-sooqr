<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Category Source Option Source model
 */
class Cms implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 0, 'label' => __('No')],
            ['value' => 1, 'label' => __('Yes, all')],
            ['value' => 2, 'label' => __('Yes, selection')],
        ];
    }
}
