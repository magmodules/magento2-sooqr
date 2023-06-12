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
 * Main Image Option Source model
 */
class MainImage implements OptionSourceInterface
{

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
        return [
            $this->getPositionSource(),
            $this->getMediaImageTypesSource()
        ];
    }

    /**
     * @return array
     */
    public function getPositionSource(): array
    {
        return [
            'label' => __('By position'),
            'value' => [
                ['value' => '', 'label' => __('First Image (default)')],
                ['value' => 'last', 'label' => __('Last Image')]
            ],
            'optgroup-name' => __('position')
        ];
    }

    /**
     * @return array
     */
    public function getMediaImageTypesSource(): array
    {
        $imageSource = [];
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('frontend_input', 'media_image')->create();
        /** @var AbstractAttribute $attribute */
        foreach ($this->attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
            if ($attribute->getIsVisible()) {
                $imageSource[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $this->getLabel($attribute)
                ];
            }
        }
        usort($imageSource, function ($a, $b) {
            return strcmp($a["label"], $b["label"]);
        });

        return [
            'label' => __('Media Image Types'),
            'value' => $imageSource,
            'optgroup-name' => __('image-types')
        ];
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
