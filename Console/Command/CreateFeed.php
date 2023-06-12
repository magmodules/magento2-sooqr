<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Sooqr\Api\Generate\RepositoryInterface as GenerateFeedRepository;
use Magmodules\Sooqr\Console\CommandOptions\CreateFeedOptions;
use Magmodules\Sooqr\Model\Config\Source\FeedExecBy;
use Magmodules\Sooqr\Model\Config\Source\FeedType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to create feed
 */
class CreateFeed extends Command
{

    /**
     * Create feed command
     */
    public const COMMAND_NAME = 'sooqr:feed:create';

    /**
     * @var CreateFeedOptions
     */
    private $options;
    /**
     * @var GenerateFeedRepository
     */
    private $generateFeedRepository;
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var State *
     */
    private $state;

    /**
     * @param CreateFeedOptions $options
     * @param GenerateFeedRepository $generateFeedRepository
     * @param ConfigProvider $configProvider
     * @param State $state
     */
    public function __construct(
        CreateFeedOptions $options,
        GenerateFeedRepository $generateFeedRepository,
        ConfigProvider $configProvider,
        State $state
    ) {
        $this->options = $options;
        $this->generateFeedRepository = $generateFeedRepository;
        $this->configProvider = $configProvider;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Generate feed file');
        $this->setDefinition($this->options->getOptionsList());
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        foreach ($this->getStoreIds($input) as $storeId) {
            try {
                $result = $this->generateFeedRepository->execute(
                    (int)$storeId,
                    FeedType::FULL,
                    FeedExecBy::CLI
                );
                $output->writeln(sprintf('<info>%s</info>', $result['message']));
            } catch (\Exception $exception) {
                $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    private function getStoreIds(InputInterface $input): array
    {
        return $input->getOption('store-id')
            ? [$input->getOption('store-id')]
            : $this->configProvider->getAllEnabledStoreIds();
    }
}
