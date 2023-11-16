<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source\Configurable;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Configurable Option Source model
 */
class Option implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('No')],
            ['value' => 'parent', 'label' => __('Only Configurable Product')],
            ['value' => 'simple', 'label' => __('Only Linked Simple Products (Recommended)')],
            ['value' => 'both', 'label' => __('Configurable and Linked Simple Products')]
        ];
    }
}
