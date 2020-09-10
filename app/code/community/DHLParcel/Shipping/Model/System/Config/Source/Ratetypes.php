<?php
class DHLParcel_Shipping_Model_System_Config_Source_Ratetypes
{
    /**
     * Returns an option array for rate type options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('dhlparcel_shipping');
        $options = array(
            array(
                'value' => 'flat',
                'label' => $helper->__('Flat'),
            ),
            array(
                'value' => 'matrix',
                'label' => $helper->__('Matrix'),
            ),
        );

        return $options;
    }
}
