<?php

$tableName = $this->getOption('accountTable');
$accountTable = Doctrine::getTable($tableName);
$accountIdentifier = $accountTable->getIdentifier();

if (is_array($accountIdentifier)) {

    throw new Security_Exception('Primary key definition for table \''.$tableName.'\' cannot be composite');
}

$local = ($accountIdentifier == 'id') ? strtolower($tableName) .'_id' : $accountIdentifier;
$column = $accountTable->getColumnDefinition($accountIdentifier);

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
        
        $this->option(\'collate\', \''.$accountTable->getOption('collate').'\');
        $this->option(\'charset\', \''.$accountTable->getOption('charset').'\');
        $this->option(\'type\', \''.$accountTable->getOption('type').'\');
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
            \'foreign\'   =>  \''.$accountIdentifier.'\',
            \'onDelete\'  =>  \'CASCADE\',
            \'onUpdate\'  =>  \'CASCADE\'));
    }
}');