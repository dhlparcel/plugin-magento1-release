<?php

class DHLParcel_Shipping_Model_Service_Debug
{
    protected $active;

    /**
     * DHLParcel_Shipping_Model_Service_Debug constructor.
     */
    public function __construct()
    {
        $this->active = Mage::getStoreConfig('carriers/dhlparcel/debug');
    }

    public function log($message, $data = [])
    {
        if ($this->active) {
            Mage::log($message . ' ' . json_encode($data), Zend_Log::INFO, 'dhlparcel_shipping.log');
        }
    }
}
