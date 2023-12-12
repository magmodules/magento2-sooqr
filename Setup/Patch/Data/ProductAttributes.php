<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class ProductAttributes
 */
class ProductAttributes implements DataPatchInterface
{

    public const PRODUCT_EXCLUDE_ATT = 'sooqr_exclude';
    public const GROUP_NAME = 'Sooqr Search';

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
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addProductAttribute();
        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Adds 'Exclude for Sooqr Search' product attribute
     *
     * @return void
     */
    public function addProductAttribute()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attributeSetIds = $eavSetup->getAllAttributeSetIds(Product::ENTITY);

        foreach ($attributeSetIds as $attributeSetId) {
            $eavSetup->addAttributeGroup(Product::ENTITY, $attributeSetId, self::GROUP_NAME, 1000);
        }

        try {
            $eavSetup->addAttribute(
                Product::ENTITY,
                self::PRODUCT_EXCLUDE_ATT,
                [
                    'group' => self::GROUP_NAME,
                    'type' => 'int',
                    'label' => 'Exclude for Sooqr Search',
                    'input' => 'boolean',
                    'source' => Boolean::class,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'default' => '0',
                    'user_defined' => true,
                    'required' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false,
                    'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
                ]
            );

            $attribute = $eavSetup->getAttribute(Product::ENTITY, self::PRODUCT_EXCLUDE_ATT);
            foreach ($attributeSetIds as $attributeSetId) {
                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    $attributeSetId,
                    self::GROUP_NAME,
                    $attribute['attribute_id'],
                    110
                );
            }
        } catch (\Exception $e) {
            $this->logger->addErrorLog('addExcludeCategoryAttribute', $e->getMessage());
        }
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
    public function getAliases()
    {
        return [];
    }
}
