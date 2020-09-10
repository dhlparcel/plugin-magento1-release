<?php
$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();
$tableName = $installer->getTable('dhlparcel_shipping/piece');

if (!$conn->isTableExists($tableName)) {
    $table = $installer->getConnection()->newTable($tableName);

    $table->addColumn(
        'entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ],
        'Entity Id'
    )->addColumn(
        'label_id', Varien_Db_Ddl_Table::TYPE_TEXT, 60,
        [
            'nullable' => false,
        ],
        'Label Id'
    )->addColumn(
        'tracker_code', Varien_Db_Ddl_Table::TYPE_TEXT, 32,
        [
            'nullable' => false,
        ],
        'Tracker Code'
    )->addColumn(
        'postal_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        [
            'nullable' => false,
        ],
        'Postal Code'
    )->addColumn(
        'parcel_type', Varien_Db_Ddl_Table::TYPE_TEXT, 32,
        [
            'nullable' => false,
        ],
        'Parcel Type'
    )->addColumn(
        'piece_number', Varien_Db_Ddl_Table::TYPE_TEXT, 32,
        [
            'nullable' => false,
        ],
        'Piece Number'
    )->addColumn(
        'label_type', Varien_Db_Ddl_Table::TYPE_TEXT, 32,
        [
            'nullable' => false,
        ],
        'Label Type'
    )->addColumn(
        'is_return', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null,
        [
            'nullable' => false,
        ],
        'Is Return'
    )->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [
            'nullable' => false,
            'default'  => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
        ],
        'Created At'

    )->addIndex(
        $installer->getIdxName('dhlparcel_shipping/piece', ['label_id'],
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        ['label_id'],
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE]
    )->addIndex(
        $installer->getIdxName('dhlparcel_shipping/piece', ['tracker_code'],
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        ),
        ['tracker_code'],
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX]
    )->setComment('DHL Parcel Shipping Pieces');

    $installer->getConnection()->createTable($table);
}

$installer->endSetup();
