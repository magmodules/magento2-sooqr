<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Controller\Installation;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Installation Index Controller
 */
class Index implements ActionInterface
{

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * Index constructor.
     *
     * @param ConfigProvider $configProvider
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        ConfigProvider $configProvider,
        JsonFactory $resultJsonFactory
    ) {
        $this->configProvider = $configProvider;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();
        return $result->setData($this->getData());
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        $json = [];
        foreach ($this->configProvider->getAllEnabledStoreIds() as $sId) {
            $store = $this->configProvider->getStore($sId);
            $json['feeds'][$sId] = [
                'name' => $store->getName(),
                'extension' => 'Magmodules_Sooqr',
                'platform_version' => $this->configProvider->getMagentoVersion(),
                'extension_version' => $this->configProvider->getExtensionVersion(),
            ];
        }
        return $json;
    }
}
