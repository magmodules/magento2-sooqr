<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Cms
 *
 * @package Magmodules\Sooqr\Model\System\Config\Source
 */
class Cms implements ArrayInterface
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
                ['value' => '1', 'label' => __('Yes, all')],
                ['value' => '2', 'label' => __('Yes, selection')],
            ];
        }
        return $this->options;
    }
}
