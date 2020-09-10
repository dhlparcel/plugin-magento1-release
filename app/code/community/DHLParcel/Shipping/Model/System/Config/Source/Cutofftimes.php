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

class DHLParcel_Shipping_Model_System_Config_Source_Cutofftimes
{
    /**
     * Returns an option array for rate type options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        $range = range(900, 95*900, 900);
        foreach ($range as $time) {
            $options[] = array (
                'value' => date('H:i', $time),
                'label' => date('H:i', $time)
            );
        }

        $options[] = array (
            'value' => '23:59',
            'label' => '23:59'
        );

        return $options;
    }
}
