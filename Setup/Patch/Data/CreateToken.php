<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

/**
 * Setup data patch class to create token
 */
class CreateToken implements DataPatchInterface
{

    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param GeneralHelper $generalHelper
     */
    public function __construct(
        GeneralHelper $generalHelper,
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter
    ) {
        $this->generalHelper = $generalHelper;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        if (!$this->generalHelper->getToken()) {
            $this->configWriter->save(
                GeneralHelper::XPATH_TOKEN,
                $this->getRandomString(),
                'default',
                0
            );
        }
        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @return string
     */
    private function getRandomString(): string
    {
        $token = '';
        $chars = str_split("abcdefghijklmnopqrstuvwxyz0123456789");
        for ($i = 0; $i < 64; $i++) {
            $token .= $chars[array_rand($chars)];
        }

        return (string)$token;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
