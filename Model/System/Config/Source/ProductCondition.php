<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ProductCondition
 *
 * @package Magmodules\Sooqr\Model\System\Config\Source
 */
class ProductCondition implements ArrayInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => 'new', 'label' => __('New')],
                ['value' => 'refurbished', 'label' => __('Refurbished')],
                ['value' => 'used', 'label' => __('Used')],
            ];
        }

        return $this->options;
    }
}
