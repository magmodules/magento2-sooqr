<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source\Grouped;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Link
 *
 * @package Magmodules\Sooqr\Model\System\Config\Source\Grouped
 */
class Link implements ArrayInterface
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
                ['value' => '0', 'label' => __('No')],
                ['value' => '1', 'label' => __('Yes')],
            ];
        }
        return $this->options;
    }
}
