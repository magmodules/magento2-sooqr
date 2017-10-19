<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\ObjectManagerInterface;

class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * UpgradeData constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param ObjectManagerInterface   $objectManager
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ObjectManagerInterface $objectManager
    ) {
        $this->productMetadata = $productMetadata;
        $this->objectManager = $objectManager;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $magentoVersion = $this->productMetadata->getVersion();
            if (version_compare($magentoVersion, '2.2.0', '>=')) {
                $this->convertSerializedDataToJson($setup);
            }
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function convertSerializedDataToJson(ModuleDataSetupInterface $setup)
    {
        // Using ObjectManager for backwardscompatibility
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
            $setup->getConnection(),
            $setup->getTable('core_config_data'),
            'config_id',
            'value',
            $queryModifier
        );
    }
}
