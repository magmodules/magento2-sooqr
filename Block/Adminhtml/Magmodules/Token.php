<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Block\Adminhtml\Magmodules;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magento\Backend\Block\Template\Context;

/**
 * Class Token
 *
 * @package Magmodules\Sooqr\Block\Adminhtml\Magmodules
 */
class Token extends Field
{

    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * Version constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $generalHelper
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper
    ) {
        $this->generalHelper = $generalHelper;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $html = $this->generalHelper->getToken();
        $element->setData('text', $html);
        return parent::_getElementHtml($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _renderScopeLabel(AbstractElement $element)
    {
        return '';
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _renderInheritCheckbox(AbstractElement $element)
    {
        return '';
    }
}
