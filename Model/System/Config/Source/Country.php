<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class Country implements ArrayInterface
{

    private $countryCollectionFactory;

    public function __construct(
        CountryCollectionFactory $countryCollectionFactory
    ) {
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    public function toOptionArray()
    {
        return $this->countryCollectionFactory->create()->toOptionArray('-- ');
    }
}
