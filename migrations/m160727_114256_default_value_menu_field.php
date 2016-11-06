<?php

use yii\db\Schema;
use yii\db\Migration;

class m160727_114256_default_value_menu_field extends Migration
{
    public function safeUp()
    {
        $this->execute('ALTER TABLE {{%pages}} ALTER COLUMN menu_id SET DEFAULT 0');
//        $this->alterColumn('{{%pages}}', 'menu_id', 'default 0');
    }

    public function safeDown()
    {
        $this->alterColumn('{{%pages}}', 'menu_id', Schema::TYPE_INTEGER.' NOT NULL');
    }
}
