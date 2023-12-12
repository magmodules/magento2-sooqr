<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class Add
 */
class Add implements ActionInterface
{
    /**
     * @var Cart
     */
    private $cart;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
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
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var LogRepository
     */
    private $logger;
    /**
     * @var MessageManagerInterface
     */
    private $messageManager;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var EventManagerInterface
     */
    private $eventManager;
    /**
     * @var ResponseInterface
     */
    private $response;
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * Add constructor.
     *
     * @param FormKey $formKey
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param RedirectInterface $redirectInterface
     * @param StoreManagerInterface $storeManager
     * @param ConfigProvider $configProvider
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param Escaper $escaper
     * @param LogRepository $logger
     * @param MessageManagerInterface $messageManager
     * @param RequestInterface $request
     * @param EventManagerInterface $eventManager
     * @param ResponseInterface $response
     * @param RedirectFactory $resultRedirectFactory
     * @param UrlInterface $url
     */
    public function __construct(
        FormKey $formKey,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        RedirectInterface $redirectInterface,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper,
        LogRepository $logger,
        MessageManagerInterface $messageManager,
        RequestInterface $request,
        EventManagerInterface $eventManager,
        ResponseInterface $response,
        RedirectFactory $resultRedirectFactory,
        UrlInterface $url
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->redirect = $redirectInterface;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->eventManager = $eventManager;
        $this->response = $response;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->url = $url;
    }

    /**
     * Add product to cart
     *
     * @return Redirect
     * @throws NoSuchEntityException
     */
    public function execute(): Redirect
    {
        if (!$this->configProvider->addToCartController()) {
            $this->messageManager->addErrorMessage(__('Controller disabled in admin'));
            return $this->goBack();
        }

        try {
            $product = $this->initProduct();
            $params = [
                'form_key' => $this->formKey->getFormKey(),
                'product' => $product->getId(),
                'qty' => $this->request->getParam('qty', 1) ?: 1,
            ];
            $this->cart->addProduct($product, $params);
            $this->cart->save();

            $this->eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->request, 'response' => $this->response]
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
            if ($this->checkoutSession->getUseNotice(true)) {
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
            if (!$url = $this->checkoutSession->getRedirectUrl(true)) {
                $url = $this->redirect->getRedirectUrl($this->getCartUrl());
            }
            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->logger->addErrorLog('addProductToCart', $e->getMessage());
            return $this->goBack();
        }
    }

    /**
     * @param null $backUrl
     * @return Redirect
     * @throws NoSuchEntityException
     */
    private function goBack($backUrl = null)
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($backUrl || $backUrl = $this->getBackUrl($this->redirect->getRefererUrl())) {
            $resultRedirect->setUrl($backUrl);
        }

        return $resultRedirect;
    }

    /**
     * Get resolved back url
     *
     * @param string|null $defaultUrl
     * @return mixed|null|string
     * @throws NoSuchEntityException
     */
    private function getBackUrl($defaultUrl = null)
    {
        $returnUrl = $this->request->getParam('return_url');
        if ($returnUrl && $this->isInternalUrl($returnUrl)) {
            $this->messageManager->getMessages()->clear();
            return $returnUrl;
        }

        if ($this->shouldRedirectToCart() || $this->request->getParam('in_cart')) {
            if ($this->request->getActionName() == 'add' && !$this->request->getParam('in_cart')) {
                $this->checkoutSession->setContinueShoppingUrl($this->redirect->getRefererUrl());
            }
            return $this->url->getUrl('checkout/cart');
        }

        return $defaultUrl;
    }

    /**
     * Check if URL corresponds store
     *
     * @param string $url
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isInternalUrl($url)
    {
        if (strpos($url, 'http') === false) {
            return false;
        }

        /**
         * Url must start from base secure or base unsecure url
         */
        /** @var $store Store */
        $store = $this->storeManager->getStore();
        $unsecure = strpos($url, (string) $store->getBaseUrl()) === 0;
        $secure = strpos($url, (string) $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true)) === 0;
        return $unsecure || $secure;
    }

    /**
     * Initialize product instance from request data
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function initProduct()
    {
        $productId = (int)$this->request->getParam('product');
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
        return $this->url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function shouldRedirectToCart()
    {
        return $this->scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            ScopeInterface::SCOPE_STORE
        );
    }
}
