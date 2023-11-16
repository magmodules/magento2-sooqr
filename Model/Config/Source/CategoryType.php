<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Category Type Option Source model
 */
class CategoryType implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'include', 'label' => __('Include by Category')],
            ['value' => 'exclude', 'label' => __('Exclude by Category')]
        ];
    }
}
