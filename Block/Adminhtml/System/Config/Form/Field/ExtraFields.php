<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\DataObject;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class ExtraFields
 *
 * @package Magmodules\Sooqr\Block\Adminhtml\System\Config\Form\Field
 */
class ExtraFields extends AbstractFieldArray
{

    /**
     * @var
     */
    private $attributeRenderer;

    /**
     * Render block
     */
    public function _prepareToRender()
    {
        $this->addColumn('attribute', [
            'label'    => __('Attribute'),
            'renderer' => $this->getAttributeRenderer()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Returns render of stores
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    public function getAttributeRenderer()
    {
        if (!$this->attributeRenderer) {
            try {
                $this->attributeRenderer = $this->getLayout()->createBlock(
                    '\Magmodules\Sooqr\Block\Adminhtml\System\Config\Form\Field\Renderer\Attributes',
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            } catch (\Exception $e) {
                $this->attributeRenderer = [];
            }
        }

        return $this->attributeRenderer;
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     */
    public function _prepareArrayRow(DataObject $row)
    {
        $attribute = $row->getAttribute();
        $options = [];
        if ($attribute) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
