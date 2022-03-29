<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Wishlist;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Wishlist\Controller\IndexInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\AuthenticationStateInterface;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NotFoundException;
use Magento\Wishlist\Helper\Data as WishlistHelper;
use Magento\Framework\App\Response\RedirectInterface;
use Magmodules\Sooqr\Logger\GeneralLoggerInterface;

/**
 * Add to wishlist controller
 */
class Add extends Action\Action implements IndexInterface
{
    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * @var WishlistProviderInterface
     */
    private $wishlistProvider;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var WishlistHelper
     */
    private $wishlistHelper;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var GeneralLoggerInterface
     */
    private $logger;

    /**
     * @var AuthenticationStateInterface
     */
    protected $authenticationState;

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * Add constructor.
     *
     * @param GeneralHelper $generalHelper
     * @param Session $customerSession
     * @param WishlistProviderInterface $wishlistProvider
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistHelper $wishlistHelper
     * @param RedirectInterface $redirect
     * @param GeneralLoggerInterface $logger
     * @param Context $context
     */
    public function __construct(
        GeneralHelper $generalHelper,
        Session $customerSession,
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        WishlistHelper $wishlistHelper,
        RedirectInterface $redirect,
        GeneralLoggerInterface $logger,
        AuthenticationStateInterface $authenticationState,
        ScopeConfigInterface $config,
        Context $context
    ) {
        $this->generalHelper = $generalHelper;
        $this->customerSession = $customerSession;
        $this->wishlistProvider = $wishlistProvider;
        $this->productRepository = $productRepository;
        $this->wishlistHelper = $wishlistHelper;
        $this->redirect = $redirect;
        $this->logger = $logger;
        $this->authenticationState = $authenticationState;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Add to wishlist action
     *
     * We can't extend default execute method, because it works only with POST requests
     *
     * @return Redirect
     * @throws NotFoundException
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->checkIfCustomerIsLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        if (!$this->generalHelper->addToWishlistController()) {
            $this->messageManager->addErrorMessage(__('Controller disabled in admin'));
            return $resultRedirect->setPath($this->redirect->getRefererUrl());
        }

        $wishlist = $this->wishlistProvider->getWishlist();
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }

        $session = $this->customerSession;

        $requestParams = $this->getRequest()->getParams();

        if ($session->getBeforeWishlistRequest()) {
            $requestParams = $session->getBeforeWishlistRequest();
            $session->unsBeforeWishlistRequest();
        }

        $productId = isset($requestParams['product']) ? (int)$requestParams['product'] : null;

        if (!$productId) {
            return $resultRedirect->setPath($this->redirect->getRefererUrl());
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        if (!$product || !$product->isVisibleInCatalog()) {
            $this->messageManager->addErrorMessage(__('We can\'t specify a product.'));
            return $resultRedirect->setPath($this->redirect->getRefererUrl());
        }

        try {
            $buyRequest = new DataObject($requestParams);

            $result = $wishlist->addNewItem($product, $buyRequest);
            if (is_string($result)) {
                throw new LocalizedException(__($result));
            }
            if ($wishlist->isObjectNew()) {
                $wishlist->save();
            }
            $this->_eventManager->dispatch(
                'wishlist_add_product',
                ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
            );

            $referer = $session->getBeforeWishlistUrl();
            if ($referer) {
                $session->setBeforeWishlistUrl(null);
            } else {
                $referer = $this->redirect->getRefererUrl();
            }

            $this->wishlistHelper->calculate();

            $this->messageManager->addComplexSuccessMessage(
                'addProductSuccessMessage',
                [
                    'product_name' => $product->getName(),
                    'referer' => $referer
                ]
            );
        } catch (LocalizedException $e) {
            $this->logger->add('wishlist', $e->getMessage());
            $this->messageManager->addErrorMessage(
                __('We can\'t add the item to Wish List right now: %1.', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->logger->add('wishlist', $e->getMessage());
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add the item to Wish List right now.')
            );
        }

        return $resultRedirect->setPath($this->redirect->getRefererUrl());
    }

    private function checkIfCustomerIsLoggedIn()
    {
        if ($this->authenticationState->isEnabled() && !$this->customerSession->authenticate()) {
            if (!$this->customerSession->getBeforeWishlistUrl()) {
                $this->customerSession->setBeforeWishlistUrl($this->redirect->getRefererUrl());
            }
            $data = $this->getRequest()->getParams();
            unset($data['login']);
            $this->customerSession->setBeforeWishlistRequest($data);
            $this->customerSession->setBeforeRequestParams($this->customerSession->getBeforeWishlistRequest());
            $this->customerSession->setBeforeModuleName('sooqr');
            $this->customerSession->setBeforeControllerName('wishlist');
            $this->customerSession->setBeforeAction('add');

            $this->messageManager->addErrorMessage(__('You must login or register to add items to your wishlist.'));
            return false;
        }
        if (!$this->config->isSetFlag('wishlist/general/active', ScopeInterface::SCOPE_STORES)) {
            throw new NotFoundException(__('Page not found.'));
        }
        return true;
    }
}
