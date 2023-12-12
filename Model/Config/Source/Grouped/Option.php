<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source\Grouped;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Grouped Option Source model
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
            ['value' => 'parent', 'label' => __('Only Grouped Product (Recommended)')],
            ['value' => 'simple', 'label' => __('Only Linked Simple Products')],
            ['value' => 'both', 'label' => __('Grouped and Linked Simple Products')]
        ];
    }
}
