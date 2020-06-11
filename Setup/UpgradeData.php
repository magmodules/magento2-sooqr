<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Class UpgradeData
 *
 * @package Magmodules\Sooqr\Setup
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var SetupData
     */
    private $installer;

    /**
     * UpgradeData constructor.
     *
     * @param SetupData $installer
     */
    public function __construct(
        SetupData $installer
    ) {
        $this->installer = $installer;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $moduleVersion = $context->getVersion();

        if (version_compare($moduleVersion, '1.0.0', '<')) {
            $this->installer->generateAndSaveToken();
        }

        if (version_compare($moduleVersion, '1.0.2', '<')) {
            $this->installer->changeConfigPaths();
        }

        if (version_compare($moduleVersion, '1.0.10', '<')) {
            $this->installer->addProductAtribute($setup);
        }

        if (version_compare($moduleVersion, '1.0.13', '<')) {
            $this->installer->addExcludeCateroryAttribute($setup);
        }
    }
}
