<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Type class
 */
class Type
{

    /**
     * @var Data
     */
    private $data;
    /**
     * @var AttributeCollector\Data\ConfigurableKey
     */
    private $configurableKey;
    /**
     * @var AttributeCollector\Data\Parents
     */
    private $parents;

    /**
     * Data constructor.
     * @param Data $data
     * @param AttributeCollector\Data\ConfigurableKey $configurableKey
     * @param AttributeCollector\Data\Parents $parents
     */
    public function __construct(
        Data $data,
        AttributeCollector\Data\ConfigurableKey $configurableKey,
        AttributeCollector\Data\Parents $parents
    ) {
        $this->data = $data;
        $this->configurableKey = $configurableKey;
        $this->parents = $parents;
    }

    /**
     * @param array $entityIds
     * @param array $attributeMap
     * @param array $extraParameters
     * @param int $storeId
     * @param bool $addDisabled
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(
        array $entityIds,
        array $attributeMap,
        array $extraParameters,
        int $storeId = 0
    ): array {
        if (empty($entityIds)) {
            return [];
        }

        $parents = $this->parents->execute($entityIds, $extraParameters['filters']['exclude_disabled']);
        $toUnset = [];
        $parentAttributeToUse = [];
        $extraProductsToLoad = [];
        $parentAttributes = [
            'configurable' => $extraParameters['behaviour']['configurable']['use_parent_attributes'],
            'grouped' => $extraParameters['behaviour']['grouped']['use_parent_attributes'],
            'bundle' => $extraParameters['behaviour']['bundle']['use_parent_attributes']
        ];
        if ($extraParameters['behaviour']['configurable']['use_parent_url']) {
            $parentAttributes['configurable'][] = 'url';
        }
        if ($extraParameters['behaviour']['grouped']['use_parent_url']) {
            $parentAttributes['grouped'][] = 'url';
        }
        if ($extraParameters['behaviour']['bundle']['use_parent_url']) {
            $parentAttributes['bundle'][] = 'url';
        }
        if ($extraParameters['behaviour']['configurable']['use_parent_images']) {
            $parentAttributes['configurable'][] = 'image';
        }
        if ($extraParameters['behaviour']['grouped']['use_parent_images']) {
            $parentAttributes['grouped'][] = 'image';
        }
        if ($extraParameters['behaviour']['bundle']['use_parent_images']) {
            $parentAttributes['bundle'][] = 'image';
        }
        $parentType = false;

        foreach ($entityIds as $entityId) {
            if (!array_key_exists($entityId, $parents)) {
                continue;
            }

            $keys = array_keys($parents[$entityId]);
            $parentId = reset($keys);
            $parentType = reset($parents[$entityId]);

            if (!isset($extraParameters['behaviour'][$parentType])) {
                continue;
            }

            if ($extraParameters['behaviour'][$parentType]['use'] == 'simple') {
                $toUnset[] = $parentId;
            } elseif ($extraParameters['behaviour'][$parentType]['use'] == 'parent') {
                $toUnset[] = $entityId;
            }
            if (!$extraParameters['behaviour'][$parentType]['use_parent_attributes']
                && !$extraParameters['behaviour'][$parentType]['use_parent_url']
                && !$extraParameters['behaviour'][$parentType]['use_parent_images']
            ) {
                continue;
            }

            if (!empty($parentAttributes[$parentType])) {
                foreach ($parentAttributes[$parentType] as $parentAttribute) {
                    $parentAttributeToUse[$entityId][$parentAttribute] = $parentId;
                }
            }
            if (!in_array($parentId, $entityIds) && !in_array($parentId, $extraProductsToLoad)) {
                $extraProductsToLoad[] = $parentId;
            }
        }
        $data = $this->data->execute(
            array_merge($entityIds, $extraProductsToLoad),
            $attributeMap,
            $extraParameters,
            $storeId
        );
        $configkeys = $this->configurableKey->execute(array_merge($entityIds, $extraProductsToLoad));
        foreach ($data as $entityId => $productData) {
            $filtered = $this->checkExtraFilters($extraParameters['filters']['custom'], $productData);
            if (!$filtered) {
                $toUnset[] = $entityId;
            }
            if (array_key_exists($entityId, $parents)) {
                $keys = array_keys($parents[$entityId]);
                $data[$entityId]['parent_id'] = reset($keys);
            }
            if (array_key_exists($entityId, $parentAttributeToUse)) {
                foreach ($parentAttributeToUse[$entityId] as $parentAttribute => $parentId) {
                    if (!isset($data[$parentId][$parentAttribute])) {
                        continue;
                    }
                    $data[$entityId][$parentAttribute] = $data[$parentId][$parentAttribute];

                    if ($extraParameters['behaviour'][$parentType]['use_parent_url'] == 2
                        && $parentAttribute == 'url'
                    ) {
                        if (!array_key_exists($entityId, $configkeys)) {
                            continue;
                        }
                        if (array_key_exists($storeId, $configkeys[$entityId][$parentId])) {
                            $data[$entityId]['url'] .= $configkeys[$entityId][$parentId][$storeId];
                        } else {
                            $data[$entityId]['url'] .= $configkeys[$entityId][$parentId][0];
                        }
                    }
                }
            }
            if (isset($data[$entityId]['parent_id']) && isset($data[$data[$entityId]['parent_id']])) {
                if (!isset($data[$data[$entityId]['parent_id']]['type_id'])) {
                    $data[$entityId]['image_logic'] = 0;
                    continue;
                }
                $typeId = $data[$data[$entityId]['parent_id']]['type_id'];
                $data[$entityId]['image_logic'] = $extraParameters['behaviour'][$typeId]['use_parent_images'] ?? 0;
            } else {
                $data[$entityId]['image_logic'] = 0;
            }
        }

        return array_diff_key($data, array_flip($toUnset));
    }

    /**
     * Validate filters on Product Data set
     *
     * @param array $filters
     * @param array $productData
     * @return bool
     */
    private function checkExtraFilters(array $filters, array $productData): bool
    {
        foreach ($filters as $filter) {
            if (!isset($productData[$filter['attribute']])) {
                return true;
            }
            if ($productData['type_id'] != $filter['product_type']) {
                return true;
            }
            switch ($filter['condition']) {
                case 'eq':
                    return $productData[$filter['attribute']] == $filter['value'];
                case 'neq':
                    return $productData[$filter['attribute']] != $filter['value'];
                case 'gt':
                    return $productData[$filter['attribute']] > $filter['value'];
                case 'gteq':
                    return $productData[$filter['attribute']] >= $filter['value'];
                case 'lt':
                    return $productData[$filter['attribute']] < $filter['value'];
                case 'lteg':
                    return $productData[$filter['attribute']] <= $filter['value'];
                case 'in':
                    return in_array($productData[$filter['attribute']], explode(',', $filter['value']));
                case 'nin':
                    return !in_array($productData[$filter['attribute']], explode(',', $filter['value']));
                case 'like':
                    return preg_match($filter['value'], $productData[$filter['attribute']]);
                case 'empty':
                    return !$productData[$filter['attribute']];
                case 'not-empty':
                    return $productData[$filter['attribute']];
            }
        }
        return true;
    }
}
