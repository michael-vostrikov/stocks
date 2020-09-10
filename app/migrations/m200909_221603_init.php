<?php

use yii\db\Migration;

/**
 * Class m200909_221603_init
 */
class m200909_221603_init extends Migration
{
    public function up()
    {
        $this->createTable('documents', [
            'id' => $this->primaryKey(),
            'document_type_id' => $this->integer()->notNull(),
            'organization_id_address' => $this->integer()->notNull(),
            'organization_id_ur' => $this->integer()->notNull(),
            'status' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->createTable('document_positions', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer()->notNull(),
            'material_id' => $this->integer()->notNull(),
            'cnt' => $this->integer()->null(),
        ]);

        $this->createTable('document_types', [
            'id' => $this->primaryKey(),
            'credit_type' => $this->integer()->notNull(),
            'name' => $this->string(100)->notNull(),
        ]);

        $this->createTable('current_stock', [
            'organization_id_address' => $this->integer()->notNull(),
            'organization_id_ur' => $this->integer()->notNull(),
            'material_id' => $this->integer()->notNull(),
            'cnt' => $this->integer()->notNull(),
            'reserve' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('', 'current_stock', ['organization_id_address', 'organization_id_ur', 'material_id']);

        $this->addForeignKey('documents__document_types', 'documents', 'document_type_id', 'document_types', 'id');
        $this->addForeignKey('document_positions__documents', 'document_positions', 'document_id', 'documents', 'id');
    }

    public function down()
    {
        $this->dropForeignKey('document_positions__documents', 'document_positions');
        $this->dropForeignKey('documents__document_types', 'documents');

        $this->dropTable('current_stock');
        $this->dropTable('document_types');
        $this->dropTable('document_positions');
        $this->dropTable('documents');
    }
}
