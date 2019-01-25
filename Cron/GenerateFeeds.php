<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Cron;

use Magmodules\Sooqr\Model\Feed as FeedModel;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

/**
 * Class GenerateFeeds
 *
 * @package Magmodules\Sooqr\Cron
 */
class GenerateFeeds
{

    /**
     * @var FeedModel
     */
    private $feedModel;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * GenerateFeeds constructor.
     *
     * @param FeedModel     $feedModel
     * @param GeneralHelper $generalHelper
     */
    public function __construct(
        FeedModel $feedModel,
        GeneralHelper $generalHelper
    ) {
        $this->feedModel = $feedModel;
        $this->generalHelper = $generalHelper;
    }

    /**
     * Execute: Run all Sooqr Feed generation.
     */
    public function execute()
    {
        try {
            $cronEnabled = $this->generalHelper->getCronEnabled();
            if ($cronEnabled) {
                $this->feedModel->generateAll();
            }
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('Cron', $e->getMessage());
        }
    }
}
