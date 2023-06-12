<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData;

use Magento\Framework\Exception\NoSuchEntityException;
use Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data\ConfigurableKey;
use Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data\Parents;

/**
 * Type class
 */
class Type
{

    /**
     * @var ConfigurableKey
     */
    private $configurableKey;
    /**
     * @var Data
     */
    private $data;
    /**
     * @var Parents
     */
    private $parents;

    /**
     * Data constructor.
     * @param Data $data
     * @param ConfigurableKey $configurableKey
     * @param Parents $parents
     */
    public function __construct(
        Data $data,
        ConfigurableKey $configurableKey,
        Parents $parents
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
     * @param int $limit
     * @param int $page
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(
        array $entityIds,
        array $attributeMap,
        array $extraParameters,
        int $storeId = 0,
        int $limit = 10000,
        int $page = 1
    ): array {
        if (empty($entityIds)) {
            return [];
        }
        $entityIds = array_chunk($entityIds, (int)$limit);
        $entityIds = $entityIds[$page - 1] ?? $entityIds[0];
        $parents = $this->parents->execute();
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
        $configKeys = $this->configurableKey->execute(array_merge($entityIds, $extraProductsToLoad));
        foreach ($data as $entityId => $productData) {
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
                        if (!array_key_exists($entityId, $configKeys)) {
                            continue;
                        }
                        if (array_key_exists($storeId, $configKeys[$entityId][$parentId])) {
                            $data[$entityId]['url'] .= $configKeys[$entityId][$parentId][$storeId];
                        } else {
                            $data[$entityId]['url'] .= $configKeys[$entityId][$parentId][0];
                        }
                    }
                }
            }
            if (isset($data[$entityId]['parent_id']) && isset($data[$data[$entityId]['parent_id']])) {
                $data[$entityId]['image_logic'] = $extraParameters['behaviour'][
                $data[
                $data[$entityId]['parent_id']
                ]['type_id']]['use_parent_images'];
            } else {
                $data[$entityId]['image_logic'] = 0;
            }
        }
        return array_diff_key($data, array_flip($toUnset));
    }
}
