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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

/**
 * Feeds Render Block
 */
class Feeds extends Field
{

    public const PREVIEW_URL = 'sooqr/feed/preview';
    public const GRID_URL = 'sooqr/feed/index';

    /**
     * Template file name
     *
     * @var string
     */
    protected $_template = 'Magmodules_Sooqr::system/config/fieldset/table/feeds.phtml';

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
                $feedData[$storeId] = [
                    'store_id' => $storeId,
                    'code' => $store->getCode(),
                    'name' => $store->getName(),
                    'credentials' => $this->credentialsSet($storeId) ? __('Yes') : __('No'),
                    'full_url' => $this->getFeedUrl($storeId),
                    'preview_url' => $this->getUrl(self::PREVIEW_URL, ['store_id' => $storeId]),
                    'grid_url' => $this->getUrl(self::GRID_URL),
                ];
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('LocalizedException', $e->getMessage());
                continue;
            }
        }
        return $feedData;
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    private function credentialsSet(int $storeId): bool
    {
        $credentials = $this->configProvider->getCredentials($storeId);
        return count($credentials) == count(array_filter($credentials));
    }

    /**
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    private function getFeedUrl(int $storeId): string
    {
        return sprintf(
            '%ssooqr/%s',
            $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
            $this->configProvider->getFilename($storeId)
        );
    }
}
