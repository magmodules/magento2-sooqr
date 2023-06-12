<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magmodules\Sooqr\Model\Config\Source\Conditions as ConditionsSource;

/**
 * HTML select for Conditions
 */
class Conditions extends Select
{

    /**
     * @var ConditionsSource
     */
    private $conditions;

    /**
     * Conditions constructor.
     *
     * @param Context $context
     * @param ConditionsSource $conditions
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConditionsSource $conditions,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->conditions = $conditions;
    }

    /**
     * @inheritDoc
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->conditions->toOptionArray() as $condition) {
                $this->addOption($condition['value'], $condition['label']);
            }
        }

        return parent::_toHtml();
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
