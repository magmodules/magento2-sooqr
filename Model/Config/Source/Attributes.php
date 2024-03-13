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
 * Attributes Option Source model
 */
class Attributes implements OptionSourceInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;
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
        $options = $this->getAttributesArray();
        array_unshift($options, ['value' => '', 'label' => __('--please select')]);
        return $options;
    }

    /**
     * @return array
     */
    public function getAttributesArray(): array
    {
        $attributes = [
            ['value' => 'attribute_set_id', 'label' => __('Attribute Set ID')],
            ['value' => 'attribute_set_name', 'label' => __('Attribute Set Name')],
            ['value' => 'type_id', 'label' => __('Product Type')],
            ['value' => 'entity_id', 'label' => __('Product Id')],
        ];

        $exclude = $this->getNonAvailableAttributes();
        $searchCriteria = $this->searchCriteriaBuilder->create();
        /** @var AbstractAttribute $attribute */
        foreach ($this->attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
            if ($attribute->getIsVisible() && !in_array($attribute->getAttributeCode(), $exclude)) {
                $attributes[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $this->getLabel($attribute)
                ];
            }
        }

        usort(
            $attributes,
            function ($a, $b) {
                return strcmp((string)$a["label"], (string)$b["label"]);
            }
        );

        return $attributes;
    }

    /**
     * @return array
     */
    public function getNonAvailableAttributes(): array
    {
        return ['categories', 'gallery', 'category_ids', 'quantity_and_stock_status', 'price', 'special_price'];
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
