<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source Type Option Source model
 */
class SourceType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'static', 'label' => 'Static Values'],
            ['value' => 'attribute', 'label' => 'Use Attribute']
        ];
    }
}
