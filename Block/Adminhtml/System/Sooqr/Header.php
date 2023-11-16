<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Block\Adminhtml\System\Sooqr;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * System Configuration Module information Block
 */
class Header extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Magmodules_Sooqr::system/config/fieldset/header.phtml';

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('sooqr');
        return $this->toHtml();
    }
}
