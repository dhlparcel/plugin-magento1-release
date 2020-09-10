<?php
/**
 * Dhl Shipping
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 *  PHP version 5.6+
 *
 *  @category  Dhlparcel
 *  @author    Shin Ho <plugins@dhl.com>
 *  @author    Rudger Gravenstein <plugins@dhl.com>
 *  @author    Ron Oerlemans <plugins@dhl.com>
 *  @copyright ${YEAR} DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Model_System_Config_Backend_Matrixrateps extends Mage_Core_Model_Config_Data
{
    /**
     * Upload a new csv file.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('dhlparcel_shipping/matrixrateps')->uploadAndImport($this);
    }
}
