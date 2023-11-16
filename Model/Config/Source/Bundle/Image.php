<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source\Bundle;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Bundle Image Option Source model
 */
class Image implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '0', 'label' => __('No')],
            ['value' => '1', 'label' => __('Yes')],
            ['value' => '2', 'label' => __('Only if Empty (Recommended)')],
            ['value' => '3', 'label' => __('Combine, simple images first')],
            ['value' => '4', 'label' => __('Combine, parent images first')]
        ];
    }
}
