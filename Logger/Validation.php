<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Logger;

use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

class Validation extends Logger implements ValidationLoggerInterface
{

    /**
     * @var Json
     */
    private $json;

    /**
     * @param Json   $json
     * @param string $name
     * @param array  $handlers
     * @param array  $processors
     */
    public function __construct(
        Json $json,
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        $this->json = $json;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * {@inheritDoc}
     */
    public function add($type, $data)
    {
        if (is_array($data) || is_object($data)) {
            $this->addRecord(static::INFO, $type . ': ' . $this->json->serialize($data));
        } else {
            $this->addRecord(static::INFO, $type . ': ' . $data);
        }
    }
}
