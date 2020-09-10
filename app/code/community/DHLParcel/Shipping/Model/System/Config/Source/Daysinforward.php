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
 *  @copyright 2018 DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

class DHLParcel_Shipping_Model_System_Config_Source_Daysinforward
{
    /**
     * Returns an option array for rate type options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('dhlparcel_shipping');
        $options = array (
            array (
                'value' => 1,
                'label' => $helper->__('1 day')
            )
        );

        for ($i = 2; $i <= 14; $i++) {
            $options[] = array (
                'value' => $i,
                'label' => $helper->__('%d days', $i)
            );
        }

        return $options;
    }
}
