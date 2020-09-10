<?php
class DHLParcel_Shipping_Model_Resource_Matrixrate_Collection
    extends DHLParcel_Shipping_Model_Resource_CollectionAbstract
{
    /**
     * Define resource model and item.
     */
    protected function _construct()
    {
        $this->_init('dhlparcel_shipping/matrixrate');
        /** @noinspection PhpDeprecationInspection */
        $this->_shipTable       = $this->getMainTable();
        $this->_countryTable    = $this->getTable('directory/country');
    }
}
