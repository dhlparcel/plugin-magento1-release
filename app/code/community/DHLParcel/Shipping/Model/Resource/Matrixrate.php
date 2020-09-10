<?php
class DHLParcel_Shipping_Model_Resource_Matrixrate extends DHLParcel_Shipping_Model_Resource_MatrixrateAbstract
{
    /**
     * Define main table and id field name.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dhlparcel_shipping/matrixrate', 'id');
        $this->_fileType = 'DOOR';
    }
}
