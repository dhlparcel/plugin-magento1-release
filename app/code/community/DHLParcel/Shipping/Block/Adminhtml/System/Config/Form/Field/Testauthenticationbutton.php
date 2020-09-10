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

class DHLParcel_Shipping_Block_Adminhtml_System_Config_Form_Field_Testauthenticationbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself
     *
     * @return DHLParcel_Shipping_Block_Adminhtml_System_Config_Form_Testauthenticationbutton
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setTemplate('dhlparcel/system/config/authentication_button.phtml');

        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        unset($element);
        return $this->_toHtml();
    }

    public function getAjaxUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/dhlparcel_system/testauthentication');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData([
                'id'      => 'dhlparcel_testauthentication',
                'label'   => $this->helper('adminhtml')->__('Test API Credentials'),
                'onclick' => 'javascript:testAuthentication(); return false;'
            ]);

        return $button->toHtml();
    }
}