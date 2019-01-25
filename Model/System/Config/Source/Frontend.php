<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Frontend
 *
 * @package Magmodules\Sooqr\Model\System\Config\Source
 */
class Frontend implements ArrayInterface
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
                ['value'=>'4', 'label'=> __('Version 4 (Responsive)')],
            ];
        }
        return $this->options;
    }
}
