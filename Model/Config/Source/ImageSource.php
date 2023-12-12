<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Image Source Option Source model
 */
class ImageSource implements OptionSourceInterface
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
     * @param Repository $attributeRepository
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
    public function toOptionArray(): array
    {
        $imageSource = [];

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('frontend_input', 'media_image')
            ->create();

        /** @var AbstractAttribute $attribute */
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
     * @param AbstractAttribute $attribute
     *
     * @return string
     */
    public function getLabel(AbstractAttribute $attribute): string
    {
        return str_replace("'", '', (string)$attribute->getFrontendLabel());
    }
}
