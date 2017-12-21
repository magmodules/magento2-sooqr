<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magmodules\Sooqr\Model\Feed\Proxy as FeedModel;
use Magmodules\Sooqr\Helper\General\Proxy as GeneralHelper;
use Magento\Framework\App\State as AppState;

/**
 * Class FeedGenerate
 *
 * @package Magmodules\Sooqr\Console\Command
 */
class FeedGenerate extends Command
{

    const COMMAND_NAME = 'sooqr:feed:generate';
    /**
     * @var FeedModel
     */
    private $feedModel;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var AppState
     */
    private $appState;

    /**
     * FeedGenerate constructor.
     *
     * @param FeedModel     $feedModel
     * @param GeneralHelper $generalHelper
     * @param AppState      $appState
     */
    public function __construct(
        FeedModel $feedModel,
        GeneralHelper $generalHelper,
        AppState $appState
    ) {
        $this->feedModel = $feedModel;
        $this->generalHelper = $generalHelper;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     *  {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Generate Sooqr XML Feed');
        $this->addOption(
            'store-id',
            null,
            InputOption::VALUE_OPTIONAL,
            'Store ID of the export feed. If not specified all enabled stores will be exported'
        );
        parent::configure();
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getOption('store-id');
        $this->appState->setAreaCode('frontend');

        if (empty($storeId) || !is_numeric($storeId)) {
            $output->writeln('<info>Start Generating feed for all stores</info>');
            $storeIds = $this->generalHelper->getEnabledArray('magmodules_sooqr/generate/enable');
            foreach ($storeIds as $storeId) {
                $result = $this->feedModel->generateByStore($storeId, 'cli');
                $msg = sprintf(
                    'Store ID %s: Generated feed with %s product in %s',
                    $storeId,
                    $result['qty'],
                    $result['time']
                );
                $output->writeln($msg);
            }
        } else {
            $output->writeln('<info>Start Generating feed for Store ID ' . $storeId . '</info>');
            $result = $this->feedModel->generateByStore($storeId, 'cli');
            $msg = sprintf(
                'Store ID %s: Generated feed with %s product in %s',
                $storeId,
                $result['qty'],
                $result['time']
            );
            $output->writeln($msg);
        }
    }
}
