<?php

use yii\db\Schema;

class m140701_090001_init extends \yii\db\Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        if ($this->db->driverName === 'pgsql') {
            $this->execute('CREATE TYPE pages_type AS ENUM (\'system\', \'user-defined\');');
        }

        // Create 'pages' table
        $this->createTable('{{%pages}}', [
            'id'            => $this->primaryKey(),
            'type'          => "pages_type NOT NULL DEFAULT 'user-defined'",
            'template_id'   => $this->integer()->unsigned()->notNull(),
            'homepage'      => $this->integer(3)->unsigned()->notNull()->defaultValue('0'),
            'active'        => $this->integer(3)->unsigned()->notNull()->defaultValue('1'),
            'position'      => $this->integer()->unsigned()->notNull(),
            'created_at'    => $this->integer()->unsigned()->notNull(),
            'updated_at'    => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);
        
        $this->createIndex('template_id', '{{%pages}}', 'template_id');
        
        // Create 'pages_lang' table
        $this->createTable('{{%pages_lang}}', [
            'page_id'       => $this->integer()->notNull(),
            'language'      => $this->string(10)->notNull(),
            'name'          => $this->string()->notNull(),
            'title'         => $this->string()->notNull(),
            'content'       => $this->text()->notNull(),
            'created_at'    => $this->integer()->unsigned()->notNull(),
            'updated_at'    => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);
        
        $this->addPrimaryKey('page_id_language', '{{%pages_lang}}', ['page_id', 'language']);
        $this->createIndex('language', '{{%pages_lang}}', 'language');
        $this->addForeignKey('FK_PAGES_LANG_PAGE_ID', '{{%pages_lang}}', 'page_id', '{{%pages}}', 'id', 'CASCADE', 'RESTRICT');
        
        // Create 'page_templates' table
        $this->createTable('{{%page_templates}}', [
            'id'                    => $this->primaryKey(),
            'name'                  => $this->string()->notNull(),
            'layout_model'          => $this->string()->notNull(),
            'active'                => $this->integer(3)->unsigned()->notNull()->defaultValue('1'),
            'created_at'            => $this->integer()->unsigned()->notNull(),
            'updated_at'            => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);
        
        // Insert the default template
        $this->insert('{{%page_templates}}', [
            'name'          => 'Default',
            'layout_model'  => 'Main',
            'created_at'    => time(),
            'updated_at'    => time()
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('page_templates');
        $this->dropTable('pages_lang');
        $this->dropTable('pages');        
    }
}
