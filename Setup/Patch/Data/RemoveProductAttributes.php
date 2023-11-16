<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

class RemoveProductAttributes implements DataPatchInterface
{

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
     * ProductAttributes constructor.
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
        return [
            ProductAttributes::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->removeProductAttribute();
        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Removed 'Exclude for Sooqr Search' product attribute
     *
     * @return void
     */
    public function removeProductAttribute()
    {
        try {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $eavSetup->removeAttribute(Product::ENTITY, ProductAttributes::PRODUCT_EXCLUDE_ATT);
        } catch (\Exception $e) {
            $this->logger->addErrorLog('removeProductAttribute', $e->getMessage());
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
