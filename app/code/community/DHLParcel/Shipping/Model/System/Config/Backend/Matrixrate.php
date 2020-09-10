<?php
class DHLParcel_Shipping_Model_System_Config_Backend_Matrixrate extends Mage_Core_Model_Config_Data
{
    /**
     * Upload a new csv file.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('dhlparcel_shipping/matrixrate')->uploadAndImport($this);
    }
}
