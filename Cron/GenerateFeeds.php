<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Cron;

use Magmodules\Sooqr\Model\Generate;
use Psr\Log\LoggerInterface;

class GenerateFeeds
{
    private $generate;
    private $logger;

    /**
     * GenerateFeeds constructor.
     * @param Generate $generate
     * @param LoggerInterface $logger
     */
    public function __construct(
        Generate $generate,
        LoggerInterface $logger
    ) {
        $this->generate = $generate;
        $this->logger = $logger;
    }

    /**
     * Execute: Run all Sooqr Feed generation.
     */
    public function execute()
    {
        try {
            $this->generate->generateAll();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
