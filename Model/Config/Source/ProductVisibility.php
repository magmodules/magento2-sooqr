<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Product Visibility Option Source model
 */
class ProductVisibility implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('Not Visible Individually')],
            ['value' => '2', 'label' => __('Catalog')],
            ['value' => '3', 'label' => __('Search')],
            ['value' => '4', 'label' => __('Catalog, Search')]
        ];
    }
}
