<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magmodules\Sooqr\Logger\GeneralLoggerInterface;

/**
 * Class Add
 */
class Add extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var Cart
     */
    protected $cart;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var FormKey
     */
    private $formKey;
    /**
     * @var RedirectInterface
     */
    private $redirect;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var GeneralLoggerInterface
     */
    private $logger;

    /**
     * Add constructor.
     *
     * @param Context $context
     * @param FormKey $formKey
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param RedirectInterface $redirectInterface
     * @param StoreManagerInterface $storeManager
     * @param GeneralHelper $generalHelper
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        RedirectInterface $redirectInterface,
        StoreManagerInterface $storeManager,
        GeneralHelper $generalHelper,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        Validator $formKeyValidator,
        Escaper $escaper,
        GeneralLoggerInterface $logger
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->redirect = $redirectInterface;
        $this->storeManager = $storeManager;
        $this->generalHelper = $generalHelper;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper;
        $this->logger = $logger;
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * @return Redirect
     * @throws NoSuchEntityException
     */
    public function execute(): Redirect
    {
        if (!$this->generalHelper->getAddToCartController()) {
            $this->messageManager->addErrorMessage(__('Controller disabled in admin'));
            return $this->goBack();
        }

        try {
            $product = $this->initProduct();
            $params = [
                'form_key' => $this->formKey->getFormKey(),
                'product' => $product->getId(),
                'qty' => $this->getRequest()->getParam('qty', 1) ?: 1,
            ];
            $this->cart->addProduct($product, $params);
            $this->cart->save();

            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->cart->getQuote()->getHasError()) {
                $message = __(
                    'You added %1 to your shopping cart.',
                    $product->getName()
                );
                $this->messageManager->addSuccessMessage($message);
                return $this->goBack($this->getCartUrl());
            }
            return $this->goBack();
        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->escaper->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->escaper->escapeHtml($message)
                    );
                }
            }
            if (!$url = $this->_checkoutSession->getRedirectUrl(true)) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }
            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->logger->add('addProductToCart', $e->getMessage());
            return $this->goBack();
        }
    }

    /**
     * @param null $backUrl
     * @return Redirect
     */
    protected function goBack($backUrl = null)
    {
        return parent::_goBack($backUrl);
    }

    /**
     * Initialize product instance from request data
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        $storeId = $this->storeManager->getStore()->getId();
        return $this->productRepository->getById($productId, false, $storeId);
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }
}
