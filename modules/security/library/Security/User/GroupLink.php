<?php

if (!$tableName = Security_System::getInstance()->getOption('accountTable')) {

    throw new Security_Exception('You must create an object that extends Security_User to use the Security module');
}

if (!$columns = Doctrine::getTable($tableName)->getColumns()) {

    throw new Security_Exception('Security module could not get column definitions for table \''.$tableName.'\'');
}

if (!$identifier = Doctrine::getTable($tableName)->getIdentifier()) {

    throw new Security_Exception('No primary key definition for table \''.$tableName.'\'');
}

if (is_array($identifier)) {

    throw new Security_Exception('Primary key definition for table \''.$tableName.'\' cannot be composite');
}

$local = ($identifier == 'id') ? strtolower($tableName) .'_id' : $identifier;
$column = $columns[$identifier];

eval('
class Group'.$tableName.' extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName(\'security_group_'.strtolower($tableName).'\');
        
        $this->hasColumn(\'group_id\', \'integer\', 4, array(
            \'unsigned\'      =>  true,
            \'primary\'       =>  true,
            \'notnull\'       =>  true,
            \'autoincrement\' =>  false));

        $this->hasColumn(\''.$local.'\', \''.$column['type'].'\', '.(!empty($column['length']) ? $column['length'] : 'null').', array(
            \'fixed\'         =>  '.(!empty($column['fixed']) ? 'true' : 'false').',
            \'unsigned\'      =>  '.(!empty($column['unsigned']) ? 'true' : 'false').',
            \'primary\'       =>  '.(!empty($column['primary']) ? 'true' : 'false').',
            \'notnull\'       =>  '.(!empty($column['notnull']) ? 'true' : 'false').',
            \'autoincrement\' =>  false));
        
    }

    public function setUp()
    {
        parent::setUp();
        
        $this->hasOne(\'Group\', array(
            \'local\'     =>  \'group_id\',
            \'foreign\'   =>  \'id\',
            \'onDelete\'  =>  \'CASCADE\',
            \'onUpdate\'  =>  \'CASCADE\'));

        $this->hasOne(\''.$tableName.'\', array(
            \'local\'     =>  \''.$local.'\',
            \'foreign\'   =>  \''.$identifier.'\',
            \'onDelete\'  =>  \'CASCADE\',
            \'onUpdate\'  =>  \'CASCADE\'));
    }
}');