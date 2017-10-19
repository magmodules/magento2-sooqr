<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ImageSource implements ArrayInterface
{

    /**
     * @var array
     */
    public $options;
    /**
     * @var Repository
     */
    private $attributeRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Attributes constructor.
     *
     * @param Repository            $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Repository $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $imageSource[] = ['value' => '', 'label' => __('First Image (default)')];
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('frontend_input', 'media_image')->create();
        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
        foreach ($this->attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
            if ($attribute->getIsVisible()) {
                $imageSource[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $this->getLabel($attribute)
                ];
            }
        }

        return $imageSource;
    }

    /**
     * @param $attribute
     *
     * @return mixed
     */
    public function getLabel($attribute)
    {
        return str_replace("'", '', $attribute->getFrontendLabel());
    }
}
