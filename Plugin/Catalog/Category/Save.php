<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Plugin\Catalog\Category;

use Magento\Catalog\Model\ResourceModel\Category as Subject;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Category Save Plugin that saves a flag in core_config_data table.
 * This flag is used te check if category data is needed to be added to the data feed
 */
class Save
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * Save constructor.
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param Subject $subject
     * @param Subject $result
     * @return Subject
     */
    public function afterSave(
        Subject $subject,
        Subject $result
    ) {
        $this->configProvider->setCategoryChangedFlag(true);
        return $result;
    }
}
