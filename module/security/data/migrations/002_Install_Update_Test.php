<?php

class Install_Update_Test_002 extends Security_Migration
{
    public function up()
    {
        /*
         * Allows installer to determine if db has UPDATE access
         * 
         * Simply updates version to 2
         * 
         */
    }
    
    public function down()
    {
        /*
         * Do nothing
         */
    }
}