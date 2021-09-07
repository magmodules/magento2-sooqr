<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Observer;

use Magento\Checkout\Model\SessionFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;

/**
 * Observer to set head data
 */
class HeadData implements ObserverInterface
{
    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var SessionFactory
     */
    private $checkoutSession;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * CheckBlock constructor.
     * @param FormKey $formKey
     * @param SessionFactory $checkoutSession
     * @param UrlInterface $urlInterface
     * @param EncoderInterface $urlEncoder
     */
    public function __construct(
        FormKey $formKey,
        SessionFactory $checkoutSession,
        UrlInterface $urlInterface,
        EncoderInterface $urlEncoder
    ) {
        $this->formKey = $formKey;
        $this->checkoutSession = $checkoutSession;
        $this->urlInterface = $urlInterface;
        $this->urlEncoder = $urlEncoder;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = [
            'form_key' => $this->formKey->getFormKey(),
            'uenc' => $this->urlEncoder->encode(
                $this->urlInterface->getUrl('*/*/*', ['_use_rewrite' => true, '_current' => true])
            )
        ];
        $this->checkoutSession->create()->setSooqrHead($data);
        return $this;
    }
}