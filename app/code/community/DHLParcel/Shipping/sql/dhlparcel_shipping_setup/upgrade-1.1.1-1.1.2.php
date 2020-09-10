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
 *  @author    Elmar van Wijnen <plugins@dhl.com>
 *  @author    Ron Oerlemans <plugins@dhl.com>
 *  @copyright ${YEAR} DHLParcel
 *  @link      https://www.dhlparcel.nl/
 */

$installer = $this;
$installer->startSetup();

$installer->addAttribute("shipment", "dhlparcel_shipping_request", array(
    'type'          => 'text',
    'backend_type'  => 'text',
    'frontend_input' => 'textarea',
    'is_user_defined' => false,
    'label'         => 'DHL Shipping Request',
    'visible'       => false,
    'required'      => false,
    'user_defined'  => false,
    'searchable'    => false,
    'filterable'    => false,
    'comparable'    => false,
    'default'       => ''
));

$installer->endSetup();
