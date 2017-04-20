<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ImageSource implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'image', 'label' => 'Image'],
            ['value' => 'small_image', 'label' => 'Small Image'],
            ['value' => 'thumbnail', 'label' => 'Thumbnail'],
        ];
    }
}
