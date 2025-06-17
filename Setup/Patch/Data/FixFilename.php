<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixFilename implements DataPatchInterface
{

    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('sooqr_feed');

        // Fetch all current values
        $select = $connection->select()->from($tableName, ['entity_id', 'filename']);
        $rows = $connection->fetchAll($select);

        foreach ($rows as $row) {
            $entityId = $row['entity_id'];
            $oldFilename = $row['filename'];
            $newFilename = basename($oldFilename, '.xml');

            if ($newFilename !== $oldFilename) {
                $connection->update(
                    $tableName,
                    ['filename' => $newFilename],
                    ['entity_id = ?' => $entityId]
                );
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
