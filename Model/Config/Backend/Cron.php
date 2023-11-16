<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

class Cron extends Value
{

    private const CRON_FULL = 'crontab/default/jobs/sooqr_data/general/cron_frequency';
    private const CRON_DELTA = 'crontab/default/jobs/sooqr_data/general/cron_frequency_delta';

    /**
     * @var ValueFactory
     */
    private $configValueFactory;

    /**
     * Cron constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return Value
     * @throws LocalizedException
     */
    public function afterSave()
    {
        try {
            $this->configValueFactory->create()->load(
                self::CRON_FULL,
                'path'
            )->setValue(
                $this->getData('groups/general/fields/cron_frequency/value') ?? ''
            )->setPath(
                self::CRON_FULL
            )->save();

//            $this->configValueFactory->create()->load(
//                self::CRON_DELTA,
//                'path'
//            )->setValue(
//                $this->getData('groups/general/fields/cron_frequency_delta/value') ?? ''
//            )->setPath(
//                self::CRON_DELTA
//            )->save();

        } catch (\Exception $e) {
            throw new LocalizedException(__('We can\'t save the cron expression.'));
        }

        return parent::afterSave();
    }
}
