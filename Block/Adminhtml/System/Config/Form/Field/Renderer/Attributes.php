<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magmodules\Sooqr\Model\Config\Source\Attributes as AttributesSource;

/**
 * HTML select for Product Attributes
 */
class Attributes extends Select
{

    /**
     * @var array
     */
    private $attribute = [];
    /**
     * @var AttributesSource
     */
    private $attributes;

    /**
     * Attributes constructor.
     *
     * @param Context $context
     * @param AttributesSource $attributes
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributesSource $attributes,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributes = $attributes;
    }

    /**
     * @inheritDoc
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getAttributeSource() as $attribute) {
                $this->addOption($attribute['value'], $attribute['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get all attributes.
     *
     * @return array
     */
    public function getAttributeSource()
    {
        if (!$this->attribute) {
            $this->attribute = $this->attributes->toOptionArray();
        }

        return $this->attribute;
    }

    /**
     * Sets name for input element.
     *
     * @param $value
     *
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setData('name', $value);
    }
}
