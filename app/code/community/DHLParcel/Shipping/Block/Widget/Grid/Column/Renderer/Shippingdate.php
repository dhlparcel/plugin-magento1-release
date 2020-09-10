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

class DHLParcel_Shipping_Block_Widget_Grid_Column_Renderer_Shippingdate extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        // Set date
        $this->setData('shipping_date', $row->getData('dhlparcel_shipping_date'));

        // Set Order
        $this->setData('order', Mage::getModel('sales/order')->load($row->getId()));

        // Check if this is a DHL order
        $shippingMethod = explode('_', $this->getData('order')->getData('shipping_method'));
        $carrier = $shippingMethod[0];

        if ($carrier != 'dhlparcel') {
            return false;
        }

        // Set Template
        $this->setTemplate('dhlparcel/sales/order/grid/columns/shippingdate.phtml');

        return $this->_toHtml();
    }

    public function getShippingDateTime()
    {
        if ($this->isSdd()) {
            $dateShipping = strtotime($this->getShippingDate());
        } else {
            $date = strtotime($this->getShippingDate());
            $dateShipping = $date-(24*60*60);
        }

        return $dateShipping;
    }

    public function isSdd()
    {
        return strpos($this->getData('order')->getData('dhlparcel_shipping_options'), 'SDD') !== false;
    }

    public function hasShippingDate()
    {
        return !empty($this->getShippingDate());
    }

    public function getShippingDate()
    {
        return $this->getData('shipping_date');
    }

    public function getFormattedDate()
    {
        return date('d-m-Y', $this->getShippingDateTime());
    }

    public function isTommorrow()
    {
        return (date('d-m-Y', time()+(24*60*60)) == $this->getFormattedDate());
    }

    public function isToday()
    {
        return (date('d-m-Y') == $this->getFormattedDate());
    }

    public function isToolate()
    {
        $dateToday = strtotime(date('Y-m-d'));

        return ($dateToday > strtotime($this->getFormattedDate()));
    }


}