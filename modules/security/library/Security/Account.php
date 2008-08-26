<?php

class Security_Account
{
    public static function fromAuth()
    {
        if (!$tableName = Security_System::getInstance()->getParam('accountTableClass')) {
            throw new Security_Account_Exception("Account table class has not been set");
        }
        
        $auth = Zend_Auth::getInstance();
        
        if ($auth->hasIdentity() && $identity = $auth->getIdentity()) {

            $columnName = Doctrine::getTable($tableName)->getIdentifier();
            
            if (!empty($identity->$columnName)) {
                
                $query = Doctrine_Query::create()
                                         ->from($tableName .' a')
                                         ->leftJoin('a.Groups g')
                                         ->addWhere('a.'. $columnName .'= ?');
                
                if (($record = $query->fetchOne(array($identity->{$columnName})))
                    && !$record instanceof Doctrine_Null) {
                
                    return $record;
                }
            }
        }
        return null;
    }
}