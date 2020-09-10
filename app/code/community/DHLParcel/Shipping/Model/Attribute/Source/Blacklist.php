<?php
class DHLParcel_Shipping_Model_Attribute_Source_Blacklist extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public $_options = array();

    public function getAllOptions()
    {
        if (empty($this->_options)) {
            /**
             * @var $shipmentOptions DHLParcel_Shipping_Model_Shipmentoptions
             */
            $shipmentOptions = Mage::getModel('dhlparcel_shipping/shipmentoptions');
            foreach ($shipmentOptions->getAllowedServiceOptionsForCustomers() as $serviceOption) {
                $this->_options[] = array(
                    'label' => $shipmentOptions->getLabelByServiceOptions($serviceOption),
                    'value' =>  $serviceOption
                );
            }
        }
        return $this->_options;
    }

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

}