<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Compare;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Product\Compare;
use Magento\Catalog\Helper\Product\Compare as CompareHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Compare\ItemFactory;
use Magento\Catalog\Model\Product\Compare\ListCompare;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Catalog\ViewModel\Product\Checker\AddToCompareAvailability;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Visitor;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Add item to compare list action.
 */
class Add extends Compare implements HttpGetActionInterface
{
    /**
     * @var AddToCompareAvailability
     */
    private $compareAvailability;
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var CompareHelper
     */
    private $compareHelper;

    public function __construct(
        Context $context,
        ItemFactory $compareItemFactory,
        CollectionFactory $itemCollectionFactory,
        CustomerSession $customerSession,
        Visitor $customerVisitor,
        ListCompare $catalogProductCompareList,
        CatalogSession $catalogSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        PageFactory $resultPageFactory,
        ProductRepositoryInterface $productRepository,
        Escaper $escaper,
        ConfigProvider $configProvider,
        CompareHelper $compareHelper,
        AddToCompareAvailability $compareAvailability = null
    ) {
        parent::__construct(
            $context,
            $compareItemFactory,
            $itemCollectionFactory,
            $customerSession,
            $customerVisitor,
            $catalogProductCompareList,
            $catalogSession,
            $storeManager,
            $formKeyValidator,
            $resultPageFactory,
            $productRepository
        );

        $this->configProvider = $configProvider;
        $this->escaper = $escaper;
        $this->compareHelper = $compareHelper;
        $this->compareAvailability = $compareAvailability
            ?: $this->_objectManager->get(AddToCompareAvailability::class);
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $productId = (int)$this->getRequest()->getParam('product');

        if (!$this->configProvider->addToCompareController()) {
            $this->messageManager->addErrorMessage(__('Controller disabled in admin'));
            return $resultRedirect->setRefererOrBaseUrl();
        }

        if ($productId && ($this->_customerVisitor->getId() || $this->_customerSession->isLoggedIn())) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                /** @var Product $product */
                $product = $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                $product = null;
            }

            if ($product && $this->compareAvailability->isAvailableForCompare($product)) {
                $this->_catalogProductCompareList->addProduct($product);
                $this->messageManager->addComplexSuccessMessage(
                    'addCompareSuccessMessage',
                    [
                        'product_name' => $this->escaper->escapeHtml($product->getName()),
                        'compare_list_url' => $this->_url->getUrl('catalog/product_compare'),
                    ]
                );

                $this->_eventManager->dispatch('catalog_product_compare_add_product', ['product' => $product]);
            }

            $this->compareHelper->calculate();
        }

        return $resultRedirect->setRefererOrBaseUrl();
    }
}
