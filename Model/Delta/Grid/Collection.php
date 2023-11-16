<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Delta\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Class Collection for creditmemo grid
 */
class Collection extends SearchResult
{

    /**
     * @return string
     */
    private function getNameAttributeId(): string
    {
        $connection = $this->_resource->getConnection();
        $selectAttributeId = $connection->select()->from(
            ['eav_attribute' => $this->_resource->getTable('eav_attribute')],
            ['attribute_id']
        )->joinLeft(
            ['eav_entity_type' => $this->getTable('eav_entity_type')],
            'eav_entity_type.entity_type_id = eav_attribute.entity_type_id',
            []
        )->where('entity_type_code = ?', 'catalog_product')
            ->where('attribute_code = ?', 'name');
        return $connection->fetchOne($selectAttributeId);
    }

    /**
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['catalog_product_entity' => $this->getTable('catalog_product_entity')],
            'main_table.product_id = catalog_product_entity.entity_id',
            [
                'sku'
            ]
        )->joinLeft(
            ['catalog_product_entity_varchar' => $this->getTable('catalog_product_entity_varchar')],
            'main_table.product_id = catalog_product_entity_varchar.entity_id',
            [
                'name' => 'value'
            ]
        )->where('attribute_id = ?', $this->getNameAttributeId());
        $this->addFilterToMap('sku', 'catalog_product_entity.sku');
        $this->addFilterToMap('name', 'catalog_product_entity_varchar.value');
    }
}
