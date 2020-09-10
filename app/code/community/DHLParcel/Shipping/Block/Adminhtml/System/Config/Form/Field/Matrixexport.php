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

class DHLParcel_Shipping_Block_Adminhtml_System_Config_Form_Field_Matrixexport
    extends Mage_Adminhtml_Block_System_Config_Form_Field
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'website' => $buttonBlock->getRequest()->getParam('website')
        );

        /** @var Mage_Adminhtml_Helper_Data $helper */
        $helper = Mage::helper('adminhtml');
        $onClick = 'setLocation(\''
                 . $helper->getUrl("adminhtml/dhlparcel_rates/export", $params)
                 . '\')';

        $data = array(
            'label'   => Mage::helper('dhlparcel_shipping')->__('Export CSV'),
            'onclick' => $onClick,
            'id'      => $element->getHtmlId(),
            'type'    => 'button',
            'class'   => 'scalable',
        );

        $buttonBlock->setData($data);
        $html = $buttonBlock->toHtml();

        return $html;
    }
}
