<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Loader implements OptionSourceInterface
{

    /**
     * @var array
     */
    public $options;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (!$this->options) {
            $this->options = [
                ['value' => '', 'label' => __('Standard')],
                ['value' => 'custom', 'label' => __('Custom')],
            ];
        }
        return $this->options;
    }
}
