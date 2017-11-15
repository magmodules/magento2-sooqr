<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigModel;
use Magmodules\Sooqr\Logger\SooqrLogger;

/**
 * Class Config
 *
 * @package Magmodules\Sooqr\Helper
 */
class Config extends AbstractHelper
{

    const XPATH_CONVERT_RUN = 'magmodules_sooqr/tast/convert_run';
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    /**
     * @var SooqrLogger
     */
    private $logger;
    /**
     * @var Config
     */
    private $config;

    /**
     * Tasks constructor.
     *
     * @param Context                  $context
     * @param ObjectManagerInterface   $objectManager
     * @param ResourceConnection       $resource
     * @param ProductMetadataInterface $productMetadata
     * @param ConfigModel               $config
     * @param SooqrLogger     $logger
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        ResourceConnection $resource,
        ProductMetadataInterface $productMetadata,
        ConfigModel $config,
        SooqrLogger $logger
    ) {
        $this->objectManager = $objectManager;
        $this->resource = $resource;
        $this->productMetadata = $productMetadata;
        $this->config = $config;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     *
     */
    public function run()
    {
        $convert = $this->scopeConfig->getValue(self::XPATH_CONVERT_RUN);
        $magentoVersion = $this->productMetadata->getVersion();
        if (empty($convert) && version_compare($magentoVersion, '2.2.0', '>=')) {
            try {
                $this->convertSerializedDataToJson();
                $this->config->saveConfig(self::XPATH_CONVERT_RUN, 1, 'default', 0);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * Convert Serialzed Data fields to Json for Magento 2.2
     * Using Object Manager for backwards compatability
     */
    public function convertSerializedDataToJson()
    {
        $magentoVersion = $this->productMetadata->getVersion();
        if (version_compare($magentoVersion, '2.2.0', '>=')) {
            $connection = $this->resource
                ->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);

            $fieldDataConverter = $this->objectManager
                ->create(\Magento\Framework\DB\FieldDataConverterFactory::class)
                ->create(\Magento\Framework\DB\DataConverter\SerializedToJson::class);

            $queryModifier = $this->objectManager
                ->create(\Magento\Framework\DB\Select\QueryModifierFactory::class)
                ->create(
                    'in',
                    [
                        'values' => [
                            'path' => ['magmodules_sooqr/data/extra_fields']
                        ]
                    ]
                );

            $fieldDataConverter->convert(
                $connection,
                $connection->getTableName('core_config_data'),
                'config_id',
                'value',
                $queryModifier
            );

            return 'Fields upated!';
        } else {
            return 'Incompatible Magento Version';
        }
    }
}
