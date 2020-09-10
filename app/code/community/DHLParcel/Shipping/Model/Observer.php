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
 * @category  Dhlparcel
 * @author    Shin Ho <plugins@dhl.com>
 * @author    Ron Oerlemans <plugins@dhl.com>
 * @author    Elmar van Wijnen <plugins@dhl.com>
 * @copyright ${YEAR} DHLParcel
 * @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Model_Observer
{
    public function checkConfigContradictions(Varien_Event_Observer $event)
    {
        $store = Mage::app()->getStore();
        if ($store->getConfig('carriers/dhlparcel/b2b') == 1 && $store->getConfig('carriers/DOOR_dhlparcel/default_ea') == 1) {
            Mage::getSingleton('core/session')->addError(__("Conflict in DHLParcel configurations, send to business by default and default extra assured can't be turned on at the same time"));
        }
        if ($store->getConfig('carriers/dhlparcel/b2b') == 1 && $store->getConfig('carriers/DOOR_dhlparcel/default_age_check') == 1) {
            Mage::getSingleton('core/session')->addError(__("Conflict in DHLParcel configurations, send to business by default and default 18+ age check can't be turned on at the same time"));
        }
        if ($store->getConfig('carriers/dhlparcel_shipping_option_NBB/active') == 1 && $store->getConfig('carriers/DOOR_dhlparcel/default_no_neighbour') == 1) {
            Mage::getSingleton('core/session')->addNotice(__("Conflict in DHLParcel configurations, Shipping option 'No Neighbour Delivery' should be turned off when No neighbour is selected to be used as default"));
        }
    }
}
