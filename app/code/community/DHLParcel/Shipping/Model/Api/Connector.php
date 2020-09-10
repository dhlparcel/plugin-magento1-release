<?php

class DHLParcel_Shipping_Model_Api_Connector
{
    const AUTH_API = 'authenticate/api-key';
    protected $accessToken;
    /** @var DHLParcel_Shipping_Model_Service_Cache */
    protected $cacheService;
    /** @var DHLParcel_Shipping_Model_Service_Debug */
    protected $debugService;
    protected $failedAuthentication = false;
    protected $url = 'https://api-gw.dhlparcel.nl/';

    public function __construct()
    {
        $this->cacheService = Mage::getSingleton('dhlparcel_shipping/service_cache');
        $this->debugService = Mage::getSingleton('dhlparcel_shipping/service_debug');
        $this->url = Mage::getStoreConfig('carriers/dhlparcel/gateway_url');

    }

    /**
     * @param $endpoint
     * @param null $params
     * @param bool $expectBlank
     * @return mixed
     * @throws DHLParcel_Shipping_Model_Api_Exception
     */
    public function post($endpoint, $params = null, $expectBlank = false)
    {
        $response = $this->request(Zend_Http_Client::POST, $endpoint, $params, $expectBlank);

        if ($expectBlank) {
            return true;
        }

        $data = json_decode($response, true);
        $this->debugService->log('CONNECTOR API response decoded', ['response' => $data]);
        return $data;
    }

    /**
     * @param $endpoint
     * @param null $params
     * @param bool $expectBlank
     * @return bool|mixed
     * @throws DHLParcel_Shipping_Model_Api_Exception
     */
    public function get($endpoint, $params = null, $expectBlank = false)
    {
        $response = $this->request(Zend_Http_Client::GET, $endpoint, $params, $expectBlank);

        if ($expectBlank) {
            return true;
        }

        $data = json_decode($response, true);
        $this->debugService->log('CONNECTOR API response decoded', ['response' => $data]);
        return $data;
    }

    /**
     * @param $method
     * @param $endpoint
     * @param array $params
     * @param bool $expectBlank
     * @param bool $isRetry
     * @return string
     * @throws DHLParcel_Shipping_Model_Api_Exception
     */
    public function request($method, $endpoint, $params = [], $expectBlank = false, $isRetry = false)
    {
        $url = $this->url . $endpoint;

        $requestHeaders = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        ];

        if ($endpoint != self::AUTH_API) {
            if (empty($this->accessToken)) {
                $this->authenticate();
            }
            $requestHeaders['Authorization'] = "Bearer {$this->accessToken}";
        }

        if ($method === Zend_Http_Client::GET && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $this->debugService->log('CONNECTOR API request raw', ['method' => $method, 'endpoint' => $endpoint, 'params' => $params]);
        $curl = new Varien_Http_Adapter_Curl();
        $curl->write($method, $url, '1.1', $this->formatHeaders($requestHeaders), json_encode($params));
        $response = $curl->read();
        $this->debugService->log('CONNECTOR API header info', ['info' => $curl->getInfo()]);
        $curl->close();

        $this->debugService->log('CONNECTOR API response raw', ['response' => $response]);
        $responseCode = Zend_Http_Response::extractCode($response);

        $body = Zend_Http_Response::extractBody($response);

        if (!($responseCode >= 200 && $responseCode < 300)) {
            if ($responseCode === 401 && $endpoint !== self::AUTH_API && $isRetry === false && $this->failedAuthentication !== true) {
                // Try again after an auth
                $this->authenticate(true);
                $this->debugService->log('CONNECTOR API request failed, attempting retry');
                $body = $this->request($method, $endpoint, $params, $expectBlank, true);
            } else {
                $this->debugService->log('CONNECTOR API request failed, client exception', ['code' => $responseCode, 'response' => $response]);
                throw new DHLParcel_Shipping_Model_Api_Exception('Api request failed', $params, $response, $responseCode);
            }
        }

        if ($responseCode >= 200 && $responseCode < 300 && $body !== '' || $expectBlank === true && $body === '') {
            $this->debugService->log('CONNECTOR API request successful');
            return $body;
        }

        $this->debugService->log('CONNECTOR API request failed');
        throw new DHLParcel_Shipping_Model_Api_Exception('Api request failed', $params, $response, $responseCode);
    }

    protected function formatHeaders($rawHeaders = [])
    {
        $headers = [];
        foreach ($rawHeaders as $key => $rawHeader) {
            $headers[] = $key . ': ' . $rawHeader;
        }
        return $headers;
    }

    protected function authenticate($refresh = false)
    {
        // Prevent endless authentication calls
        if ($this->failedAuthentication) {
            // Exit early
            return;
        }
        if (!empty($this->accessToken) && $refresh === false) {
            return;
        }

        $cacheKey = $this->cacheService->createKey('accessToken');
        $accessToken = $this->cacheService->load($cacheKey);
        if ($accessToken === false || $refresh === true) {
            $response = $this->post(self::AUTH_API, [
                'userId' => trim(Mage::getStoreConfig('carriers/dhlparcel/api_user')),
                'key'    => trim(Mage::getStoreConfig('carriers/dhlparcel/api_key')),
            ]);
            if (!empty($response['accessToken'])) {
                $this->cacheService->save($response['accessToken'], $cacheKey, 720);
                $this->accessToken = $response['accessToken'];
            }
            if (empty($response['accessToken']) && $refresh === true) {
                $this->failedAuthentication = true;
            }
        } else {
            $this->accessToken = $accessToken;
        }
    }

    /**
     * @param $user_id
     * @param $key
     * @return array|bool
     */
    public function testAuthenticate($user_id, $key)
    {
        try {
            $response = $this->post(self::AUTH_API, [
                'userId' => trim($user_id),
                'key'    => trim($key),
            ]);
        } catch (Exception $e) {
            return false;
        }

        if (!isset($response['accessToken'])) {
            return false;
        }

        $accounts = $response['accountNumbers'];

        return [
            'accounts' => $accounts,
        ];
    }

    /**
     * @return string
     */
    public function getUuidV4()
    {
        // phpcs:disable
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0C2f) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0x2Aff),
            mt_rand(0, 0xffD3),
            mt_rand(0, 0xff4B)
        );
        // phpcs:enable
    }
}
