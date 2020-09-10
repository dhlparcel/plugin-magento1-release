<?php

class DHLParcel_Shipping_Model_Service_Cache
{
    const CACHE_TAG = 'dhlparcel_shipping';

    public function createKey($method, $params = [])
    {
        foreach ($params as $key => $param) {
            $params[$key] = base64_encode($param);
        }
        return 'dhl_' . $method . ':' . implode('_', $params);
    }

    /**
     * @param $data
     * @param $key
     * @param $lifetime
     * @throws Zend_Cache_Exception
     */
    public function save($data, $key, $lifetime)
    {
        try {
            if (!is_numeric($lifetime)) {
                return;
            }

            Mage::app()->getCache()->save($data, $key, ['dhlparcel_shipping'], $lifetime);
        } catch (Zend_Cache_Exception $e) {
            Mage::logException($e);
            if (Mage::getStoreConfig('carriers/dhlparcel/debug')) {
                // cache error should more or less only be thrown when programmatically something is being done wrong so will be suppressed unless working in debug mode
                throw $e;
            }
        }
    }

    /**
     * @param string $key
     * @return false|mixed
     */
    public function load($key)
    {
        return Mage::app()->getCache()->load($key);
    }
}
