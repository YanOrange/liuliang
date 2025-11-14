<?php

use think\migration\Migrator;

class Admin extends Migrator
{
    public function change()
    {
        $table = $this->table('admin', [
            'engine' => 'InnoDB',
            'comment' => 'lt_admin',
            'collation' => 'utf8mb4_general_ci'
        ]);

        //删除表
        if ($table->exists()) {
            $table->drop();
        }

        $table
            
        ;

        $table->create();
    }
}