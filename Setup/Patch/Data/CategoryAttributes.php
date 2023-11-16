<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class CategoryAttributes
 */
class CategoryAttributes implements DataPatchInterface
{

    public const CATEGORY_EXCLUDE_ATT = 'sooqr_cat_disable_export';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var LogRepository
     */
    private $logger;

    /**
     * CategoryAttributes constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LogRepository $logger
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        LogRepository $logger
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addExcludeCategoryAttribute();
        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Adds 'Disable Category from export' category attribute
     *
     * @return void
     */
    public function addExcludeCategoryAttribute()
    {
        try {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $eavSetup->addAttribute(
                Category::ENTITY,
                self::CATEGORY_EXCLUDE_ATT,
                [
                    'type' => 'int',
                    'label' => 'Disable Category from export',
                    'input' => 'select',
                    'source' => Boolean::class,
                    'global' => 1,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'sort_order' => 100,
                    'default' => 0
                ]
            );
        } catch (\Exception $e) {
            $this->logger->addErrorLog('addExcludeCategoryAttribute', $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
