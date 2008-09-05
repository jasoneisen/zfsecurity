<?php

class Install_CreateInsert_Test_001 extends Security_Migration
{
    public function up()
    {
        /*
         * Allows installer to determine if db has CREATE and INSERT access
         * 
         * Just creates a security_migration table with version of 1
         * 
         */
    }
    
    public function down()
    {
        /*
         * Sets version to 0
         * 
         * EMPTY/DROP TABLE must be done by user
         * 
         */
    }
}