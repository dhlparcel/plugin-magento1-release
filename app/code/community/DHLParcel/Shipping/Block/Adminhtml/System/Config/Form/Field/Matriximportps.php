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

class DHLParcel_Shipping_Block_Adminhtml_System_Config_Form_Field_Matriximportps
    extends Mage_Adminhtml_Block_System_Config_Form_Field
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render the element.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setType('file')
                ->removeClass('input-text');

        $html = parent::render($element);

        return $html;
    }
}
