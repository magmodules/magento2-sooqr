<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Logger;

use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

/**
 * DebugLogger uses composition to log debug data via Monolog
 */
class DebugLogger
{
    private Logger $logger;
    private Json $json;

    public function __construct(
        Logger $logger,
        Json $json
    ) {
        $this->logger = $logger;
        $this->json = $json;
    }

    /**
     * Add debug data to Log
     *
     * @param string $type
     * @param mixed $data
     * @return void
     */
    public function addLog(string $type, $data): void
    {
        $message = $type . ': ';

        if (is_array($data) || is_object($data)) {
            $message .= $this->json->serialize($data);
        } else {
            $message .= (string)$data;
        }

        $this->logger->info($message);
    }
}
