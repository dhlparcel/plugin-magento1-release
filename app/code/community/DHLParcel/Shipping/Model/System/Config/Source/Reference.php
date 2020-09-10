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

class DHLParcel_Shipping_Model_System_Config_Source_Reference
{
    /**
     * Returns an option array for reference options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        $options[] = array (
            'value' => 'order_id',
            'label' => __('Order ID')
        );

        $options[] = array (
            'value' => 'shipment_id',
            'label' => __('Shipment ID')
        );

        $options[] = array (
            'value' => 'shipment_increment_id',
            'label' => __('Shipment Increment ID')
        );

        $options[] = array (
            'value' => 'order_increment_id',
            'label' => __('Order Increment ID')
        );

        $options[] = array (
            'value' => 'order_custom',
            'label' => __('Custom Order Field')
        );

        $options[] = array (
            'value' => 'shipment_custom',
            'label' => __('Custom Shipment Field')
        );

        return $options;
    }
}
