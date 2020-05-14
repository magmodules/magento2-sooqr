<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\App\Emulation;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magmodules\Sooqr\Model\Feed as FeedModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate Feeds by CLI
 */
class FeedGenerate extends Command
{
    const COMMAND_NAME = 'sooqr:feed:generate';
    const START_GENERATE_ALL = 'Start Generating feed for all stores';
    const START_GENERATE_STORE = 'Start Generating feed for Store ID: ';
    const GENERATE_RESULT = 'Store ID %s: Generated feed with %s product in %s';
    const GENERATE_EXCEPTION = 'Store ID %s: %s';

    /**
     * @var FeedModel
     */
    private $feedModel;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var Emulation
     */
    private $appEmulation;
    /**
     * @var State
     */
    private $state;

    /**
     * FeedGenerate constructor.
     *
     * @param FeedModel $feedModel
     * @param GeneralHelper $generalHelper
     * @param Emulation $appEmulation
     * @param State $state
     */
    public function __construct(
        FeedModel $feedModel,
        GeneralHelper $generalHelper,
        Emulation $appEmulation,
        State $state
    ) {
        $this->feedModel = $feedModel;
        $this->generalHelper = $generalHelper;
        $this->appEmulation = $appEmulation;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
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
     * @throws LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getOption('store-id');
        $this->state->setAreaCode(Area::AREA_FRONTEND);

        if (empty($storeId) || !is_numeric($storeId)) {
            $output->writeln(sprintf('<info>%s</info>', self::START_GENERATE_ALL));
            $storeIds = $this->generalHelper->getEnabledArray('magmodules_sooqr/generate/enable');
            foreach ($storeIds as $storeId) {
                $this->generateFeed($storeId, $output);
            }
        } else {
            $output->writeln(sprintf('<info>%s</info>', self::START_GENERATE_STORE . $storeId));
            $this->generateFeed($storeId, $output);
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Generate Feed by Store
     *
     * @param int $storeId
     * @param OutputInterface $output
     */
    private function generateFeed($storeId, OutputInterface $output)
    {
        try {
            $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $result = $this->feedModel->generateByStore($storeId, 'cli');
            $msg = sprintf(
                self::GENERATE_RESULT,
                $storeId,
                $result['qty'],
                $result['time']
            );
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('Generate', $e->getMessage());
            $msg = sprintf(self::GENERATE_EXCEPTION, $storeId, $e->getMessage());
        } finally {
            $output->writeln($msg);
            $this->appEmulation->stopEnvironmentEmulation();
        }
    }
}
