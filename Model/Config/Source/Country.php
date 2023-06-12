<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Country Option Source model
 */
class Country implements OptionSourceInterface
{

    /**
     * @var CountryCollectionFactory
     */
    public $countryCollectionFactory;

    /**
     * Country constructor.
     *
     * @param CountryCollectionFactory $countryCollectionFactory
     */
    public function __construct(
        CountryCollectionFactory $countryCollectionFactory
    ) {
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->countryCollectionFactory->create()->toOptionArray('-- ');
    }
}
