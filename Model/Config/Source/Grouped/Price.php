<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source\Grouped;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Grouped Price Option Source model
 */
class Price implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('Minimum Price (Recommended)')],
            ['value' => 'max', 'label' => __('Maximum Price')],
            ['value' => 'total', 'label' => __('Total Price')]
        ];
    }
}
