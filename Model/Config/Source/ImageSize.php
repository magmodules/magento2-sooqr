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
class ImageSize implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('No')],
            ['value' => 'fixed', 'label' => __('Yes, fixed cached size')],
            ['value' => 'custom', 'label' => __('Yes, custom value')],
        ];
    }
}
