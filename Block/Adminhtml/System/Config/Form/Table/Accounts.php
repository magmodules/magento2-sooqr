<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Block\Adminhtml\System\Config\Form\Table;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

/**
 * Feeds Render Block
 */
class Accounts extends Field
{

    public const CONFIG_URL = 'adminhtml/system_config/edit/section/sooqr_general';
    public const CREDENTIAL_CHECK_URL = 'sooqr/credential/check';

    /**
     * Template file name
     *
     * @var string
     */
    protected $_template = 'Magmodules_Sooqr::system/config/fieldset/table/accounts.phtml';

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * Feeds constructor.
     *
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     * @param LogRepository $logRepository
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        LogRepository $logRepository
    ) {
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->logRepository = $logRepository;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('magmodules');

        return $this->toHtml();
    }

    /**
     * @return array
     */
    public function getStoreData(): array
    {
        $feedData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = (int)$store->getStoreId();
            try {
                $accountId = $this->configProvider->getCredentials($storeId)['account_id'];
                $feedData[$storeId] = [
                    'store_id' => $storeId,
                    'code' => $store->getCode(),
                    'name' => $store->getName(),
                    'account_id' => !empty($accountId) ? $accountId : '--not set',
                    'test_url' => $this->getUrl(self::CREDENTIAL_CHECK_URL, ['store' => $storeId]),
                    'edit_url' => $this->getUrl(self::CONFIG_URL, ['store' => $storeId]),
                ];
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('LocalizedException', $e->getMessage());
                continue;
            }
        }
        return $feedData;
    }
}
