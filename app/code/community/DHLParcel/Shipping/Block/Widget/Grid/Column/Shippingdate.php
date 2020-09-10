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

class DHLParcel_Shipping_Block_Widget_Grid_Column_Shippingdate extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Select  {

    protected $_options = false;

    protected function _getOptions()
    {
        if (!$this->_options) {
            $methods = array();
            $methods[] = array(
                'value' =>  '',
                'label' =>  ''
            );
            $methods[] = array(
                'value' =>  date('Y-m-d', time()+60*60*24),
                'label' =>  'Today'
            );

            $this->_options = $methods;
        }
        return $this->_options;
    }

    /**
     * @param Mage_Sales_Model_Resource_Order_Grid_Collection $collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return $this
     */
    public function filterDate($collection, $column)
    {
        $filter = $column->getFilter();
        if (!$filter) {
            return $collection;
        }

        // Add or to collection
        $columnName = $column->getData('index');
        $filterValue = $column->getFilter()->getValue();
        $collection->addFieldToFilter('main_table.' . $columnName, array (
            array('lteq' => $filterValue),
            array('null' => true)
        ));


        // Join shipping_method field
        $collection->getSelect()->join(
            'sales_flat_order',
            'main_table.entity_id = sales_flat_order.entity_id',
            array('shipping_method')
        );

        // Add status filter
        $collection->addFieldToFilter('main_table.status', array ( 'in' => array('pending', 'processing')));
        $collection->addFieldToFilter('sales_flat_order.shipping_method', array ( 'in' => array('dhlparcel_DOOR', 'dhlparcel_PS')));

        return $collection;
    }
}
