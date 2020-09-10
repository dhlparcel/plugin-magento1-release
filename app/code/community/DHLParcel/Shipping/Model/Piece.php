<?php

class DHLParcel_Shipping_Model_Piece extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'dhlparcel_shipping_piece';

    protected function _construct()
    {
        $this->_init('dhlparcel_shipping/piece');
    }
}
