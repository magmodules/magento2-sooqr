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
use Magento\Framework\App\Request\Http;

class Header extends Field
{

    const MODULE_CODE = 'magento2-sooqr';
    const MODULE_SUPPORT_LINK = 'https://support.sooqr.com/?base=';
    const MODULE_SINGUP_LINK = 'https://signup.sooqr.com/?base=';

    /**
     * @var string
     */
    protected $_template = 'Magmodules_Sooqr::system/config/fieldset/header.phtml';

    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * @var Http
     */
    private $request;

    /**
     * Header constructor.
     *
     * @param Context       $context
     * @param Http          $request
     * @param GeneralHelper $generalHelper
     */
    public function __construct(
        Context $context,
        Http $request,
        GeneralHelper $generalHelper
    ) {
        $this->generalHelper = $generalHelper;
        $this->request = $request;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('magmodules');

        return $this->toHtml();
    }

    /**
     * Image with extension and magento version.
     *
     * @return string
     */
    public function getImage()
    {
        $extVersion = $this->generalHelper->getExtensionVersion();
        $magVersion = $this->generalHelper->getMagentoVersion();

        return sprintf('https://www.magmodules.eu/logo/%s/%s/%s/logo.png', self::MODULE_CODE, $extVersion, $magVersion);
    }

    /**
     * Contact link for extension.
     *
     * @return string
     */
    public function getSingupLink()
    {
        return self::MODULE_SINGUP_LINK . $this->getBaseUrl();
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        $storeId = (int)$this->request->getParam('store', 0);
        $baseUrl = parse_url($this->generalHelper->getBaseUrl($storeId));
        if (isset($baseUrl['host'])) {
            return $baseUrl['host'];
        }
        return '';
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink()
    {
        return self::MODULE_SUPPORT_LINK . $this->getBaseUrl();
    }
}
