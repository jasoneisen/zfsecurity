<?php

class Security_Validate_Setting_DataPath extends Zend_Validate_Abstract
{
    const STRING_EMPTY = 'stringEmpty';
    
    const NOT_READABLE = 'notReadable';
    
    const MISSING_SETTINGS = 'missingSettings';
    
    const MISSING_ROUTES = 'missingSettings';
    
    const MISSING_SCHEMA = 'missingSchema';
    
    const MISSING_MIGRATION_FOLDER = 'missingMigrationFolder';
    
    const MISSING_MIGRATION_FILES = 'missingMigrationFiles';

    protected $_messageTemplates = array(
        self::STRING_EMPTY => "'%value%' is an empty string",
        self::NOT_READABLE => "'%value%' is not a readable directory",
        self::MISSING_SETTINGS => "settings.xml could not be found or read from in '%value%'",
        self::MISSING_ROUTES => "routes.xml could not be found or read from in '%value%'",
        self::MISSING_SCHEMA => "schema.yml could not be found or read from in '%value%'",
        self::MISSING_MIGRATION_FOLDER => "No 'migrations' folder found in '%value%'",
        self::MISSING_MIGRATION_FILES => "No migration classes could be found or read from in 'migrations' folder in '%value%'"
    );
    
    public function isValid($value)
    {
        $dataPath = (string) $value;
        $this->_setValue($dataPath);

        if ('' === $dataPath) {
            $this->_error(self::STRING_EMPTY);
            return false;
        }
        
        // Add a trailing slash
        if (substr($dataPath, -1, 1) != DIRECTORY_SEPARATOR) {
            $dataPath .= DIRECTORY_SEPARATOR;
        }
        
        if (!is_readable($dataPath)) {
            $this->_error(self::NOT_READABLE);
            return false;
        }
        
        if (!Zend_Loader::isReadable($dataPath . 'settings.xml')) {
            $this->_error(self::MISSING_SETTINGS);
        }
        
        if (!Zend_Loader::isReadable($dataPath . 'routes.xml')) {
            $this->_error(self::MISSING_ROUTES);
        }
        
        if (!Zend_Loader::isReadable($dataPath . 'schema.yml')) {
            $this->_error(self::MISSING_SCHEMA);
        }
        
        if (!$files = @scandir($dataPath . 'migrations')) {
            $this->_error(self::MISSING_MIGRATION_FOLDER);
        } elseif (count($files) <= 2) {
            $this->_error(self::MISSING_MIGRATION_FILES);
        }
        
        if (!empty($this->_errors)) {
            return false;
        }

        return true;
    }

}
