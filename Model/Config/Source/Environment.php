<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{

    public const PRODUCTION = 'production';
    public const DEVELOPMENT = 'development';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::PRODUCTION, 'label' => __('Production')],
            ['value' => self::DEVELOPMENT, 'label' => __('Development')],
        ];
    }
}
