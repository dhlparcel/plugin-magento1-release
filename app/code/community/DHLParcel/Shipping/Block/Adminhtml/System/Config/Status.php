<?php

class Dhlparcel_Shipping_Block_Adminhtml_System_Config_Status extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * Data Helper.
     *
     * @var DHLParcel_Shipping_Helper_Data
     */
    public $helper;

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'dhlparcel/system/config/status.phtml';

    /**
     * Dhlparcel_Shipping_Block_Adminhtml_System_Config_Status constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->helper = Mage::helper('dhlparcel_shipping');
    }

    /**
     * Render fieldset html.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
