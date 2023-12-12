<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\Api;

use Exception;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\ClientInterface as Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * API adapter class
 */
class Adapter
{
    /**
     * Endpoints
     */
    private const PRODUCTION_ENV = 'https://merchandising.sooqr.com';
    private const DEVELOPMENT_ENV = 'https://merchandising.aws-mysooqr2dev.sooqr.com';
    private const AUTH_TEST_ENDPOINT_PATH = 'api/merch/a/%s';

    /**
     * @var Json
     */
    private $json;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param Curl $curl
     * @param ConfigProvider $configProvider
     * @param Json $json
     */
    public function __construct(
        Curl $curl,
        ConfigProvider $configProvider,
        Json $json
    ) {
        $this->curl = $curl;
        $this->configProvider = $configProvider;
        $this->json = $json;
    }

    /**
     * Depends on type, perform API data transmission
     *
     * @param array $data
     * @param string $type
     *
     * @return string
     * @throws AuthenticationException
     * @throws Exception
     */
    public function execute(array $data, string $type = 'category'): string
    {
        switch ($type) {
            case 'credentials_test':
                return $this->credentialsTest($data);
            default:
                throw new Exception(__('Unknown type %1', $type)->render());
        }
    }

    /**
     * Check credentials using merchandising endpoint
     *
     * @param array $data
     *
     * @return string
     * @throws AuthenticationException
     * @throws Exception
     */
    private function credentialsTest(array $data): string
    {
        $data += $this->configProvider->getCredentials((int)$data['store_id']);
        if (!$data['api_key'] || !$data['secret'] || !$data['account_id']) {
            throw new AuthenticationException(__('Please set credentials first'));
        }

        $url = sprintf(
            $this->getApiUrl('credentials_test', (int)$data['store_id']),
            $data['account_id']
        );

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Accept', 'application/json');
        $this->curl->addHeader(
            'Authorization',
            'Basic ' . base64_encode($data['api_key'] . ':' . $data['secret'])
        );

        $this->curl->post($url, []);
        if ($this->curl->getStatus() != 200) {
            throw new AuthenticationException(__('Credential test failed'));
        }

        $result = $this->json->unserialize($this->curl->getBody());
        return (string)$result['detail'];
    }

    /**
     * @param string $type
     * @param int|null $storeId
     *
     * @return string
     * @throws Exception
     */
    public function getApiUrl(string $type, ?int $storeId = null): string
    {
        $url = $this->configProvider->isInProduction($storeId)
            ? self::PRODUCTION_ENV
            : self::DEVELOPMENT_ENV;

        switch ($type) {
            case 'credentials_test':
                return sprintf("%s/%s", $url, self::AUTH_TEST_ENDPOINT_PATH);
            default:
                throw new Exception(__('Unknown type %1', $type)->render());
        }
    }
}
