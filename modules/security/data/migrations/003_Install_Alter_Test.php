<?php

class Install_Alter_Test_002 extends Security_Migration
{
    public function up()
    {
        /*
         * Allows installer to determine if db has ALTER access
         */
         $this->changeColumn('security_migration', 'version', 'integer', array('unsigned' => 1));
    }
    
    public function down()
    {
        /*
         * Do nothing
         */
    }
}