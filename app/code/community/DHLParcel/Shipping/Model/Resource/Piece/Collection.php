<?php

class DHLParcel_Shipping_Model_Resource_Piece_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('dhlparcel_shipping/piece');
    }
}
