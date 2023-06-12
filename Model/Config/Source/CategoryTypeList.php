<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Category Type List Option Source model
 */
class CategoryTypeList implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'in', 'label' => __('Include by Category')],
            ['value' => 'nin', 'label' => __('Exclude by Category')],
        ];
    }
}
