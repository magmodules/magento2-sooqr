<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ImageSize implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('No')],
            ['value' => 'fixed', 'label' => __('Yes, fixed cached size')],
            ['value' => 'custom', 'label' => __('Yes, custom value')],
        ];
    }
}
