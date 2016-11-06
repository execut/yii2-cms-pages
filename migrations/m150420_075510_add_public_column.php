<?php

use yii\db\Schema;
use yii\db\Migration;

class m150420_075510_add_public_column extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%pages}}', 'public', $this->boolean()->notNull()->defaultValue('true'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%pages}}', 'public');
    }
}
