<?php
$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_product', DHLParcel_Shipping_Model_Carrier::PRODUCT_ATTRIBUTE_BLACKLIST_SERVICEPOINT, array(
    'label'                     => 'DHL Parcel blacklist servicepoint delivery in checkout',
    'group'                     => DHLParcel_Shipping_Model_Carrier::PRODUCT_ATTRIBUTE_GROUP,
    'input'                     => 'select',
    'type'                      => 'int',
    'source'                    => 'eav/entity_attribute_source_boolean',
    'global'                    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                   => 1,
    'required'                  => 0,
    'visible_on_front'          => 0,
    'unique'                    => false,
    'default'                   => 0
));

$installer->addAttribute('catalog_product', DHLParcel_Shipping_Model_Carrier::PRODUCT_ATTRIBUTE_BLACKLIST_GENERAL, array(
    'label'                     => 'DHL Parcel blacklist service options in checkout',
    'group'                     => DHLParcel_Shipping_Model_Carrier::PRODUCT_ATTRIBUTE_GROUP,
    'input'                     => 'multiselect',
    'type'                      => 'text',
    'source'                    => 'dhlparcel_shipping/attribute_source_blacklist',
    'backend'                   => 'eav/entity_attribute_backend_array',
    'global'                    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                   => 1,
    'required'                  => 0,
    'visible_on_front'          => 0,
    'unique'                    => false,
    'default'                   => 0
));


// Add attribute group to all attributesets
$eavModel = Mage::getModel('eav/entity_setup', 'core_setup');
foreach ($eavModel->getAllAttributeSetIds('catalog_product') as $id) {
    $attributeGroupId = $eavModel->getAttributeGroupId('catalog_product', $id, DHLParcel_Shipping_Model_Carrier::PRODUCT_ATTRIBUTE_GROUP);

    // Add servicepoint attribute
    $servicePointAttributeID = $eavModel->getAttributeId('catalog_product', DHLParcel_Shipping_Model_Carrier::PRODUCT_ATTRIBUTE_BLACKLIST_SERVICEPOINT);
    $eavModel->addAttributeToSet('catalog_product', $id, $attributeGroupId, $servicePointAttributeID);

    // Add  blacklist attribute
    $generalAttributeID = $eavModel->getAttributeId('catalog_product', DHLParcel_Shipping_Model_Carrier::PRODUCT_ATTRIBUTE_BLACKLIST_GENERAL);
    $eavModel->addAttributeToSet('catalog_product', $id, $attributeGroupId, $generalAttributeID);
}

$installer->endSetup();