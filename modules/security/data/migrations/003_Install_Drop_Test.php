<?php

class Install_Drop_Test_003 extends Security_Migration
{
    public function preUp()
    {
        /*
         * Since we cant use the migration table like the previous two,
         * we must create a table to drop
         */
         
        $this->createTable('security_drop_test');
    }
    
    public function up()
    {
        /*
         * Allows installer to determine if db has DROP access
         */
        
        $this->dropTable('security_drop_test');
    }
    
    public function down()
    {
        /*
         * Do nothing
         */
    }
}