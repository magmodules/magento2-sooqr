<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source\Bundle;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option class for Bundle Price Type
 */
class Price implements OptionSourceInterface
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
                ['value' => '', 'label' => __('Default (Recommended)')],
                ['value' => 'max', 'label' => __('Maximum Price')],
                ['value' => 'min', 'label' => __('Minimum Price')],
            ];
        }

        return $this->options;
    }
}
