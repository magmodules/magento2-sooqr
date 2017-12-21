<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ImageSize
 *
 * @package Magmodules\Sooqr\Model\System\Config\Source
 */
class ImageSize implements ArrayInterface
{

    /**
     * @var array
     */
    public $options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => '', 'label' => __('No')],
                ['value' => 'fixed', 'label' => __('Yes, fixed cached size')],
                ['value' => 'custom', 'label' => __('Yes, custom value')],
            ];
        }
        return $this->options;
    }
}
