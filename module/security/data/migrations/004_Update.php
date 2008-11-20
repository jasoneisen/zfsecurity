<?php

class Update_004 extends Security_Migration
{
    public function up()
    {
        /*
         * Adding options
         */
         $login = new SecurityOption();
         $login->name = 'loginRouteName';
         $login->save();
         
         $logout = new SecurityOption();
         $logout->name = 'postLogoutRouteName';
         $logout->save();
    }
    
    public function down()
    {
        Doctrine::getTable('SecurityOption')->findOneByName('loginRouteName')->delete();
        Doctrine::getTable('SecurityOption')->findOneByName('postLogoutRouteName')->delete();
    }
}