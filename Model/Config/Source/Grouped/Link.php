<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source\Grouped;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Grouped Link Option Source model
 */
class Link implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '0', 'label' => __('No')],
            ['value' => '1', 'label' => __('Yes')],
        ];
    }
}
