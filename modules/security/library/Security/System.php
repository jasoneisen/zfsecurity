<?php

final class Security_System
{
    private static $_instance = null;
    
    private static $_paths =    array('/library',
                                      '/models',
                                      '/models/generated');
    
    private $_dirs =            array('base'      =>  null,
                                      'configs'   =>  '/configs');
    
    private $_options =         array('accountModel'                =>  'Security_User',
                                      'accountTable'                =>  'Accounts'
                                      'useSecurityErrorController'  =>  true);
    
    private $_enabled =         array('system'    =>  false,
                                      'acl'       =>  false,
                                      'doctrine'  =>  false);
    
    private $_installed = false;
    
    private $_front = null;
    
    private $_models = null;
    
    private function __clone() {}
    
    private function __construct()
    {
        $path = self::getModuleDir();
        
        foreach ($this->_dirs as $name => $dir) {
            $this->_dirs[$name] .= $path;
        }
        
        if (($paths = self::getIncludePaths(true)) && !empty($paths)) {
            set_include_path($paths . PATH_SEPARATOR . get_include_path());
        }
        
        Zend_Loader::loadFile('Security/User/GroupLink.php');
        $this->_models = array_merge(Doctrine::loadModels($path . '/models', Doctrine::MODEL_LOADING_CONSERVATIVE),
                                     array('Group'.$this->getOption('accountModel')));
        
        try {
            
            if ($options = Doctrine::getTable('SecurityOption')->findAll()) {
                
                $this->_installed = true;
            }

        } catch (Doctrine_Connection_Exception $e) {}
        
        if ($this->isInstalled()) {
        
            foreach ($options as $option) {
            
                if (!strstr($option->tag, '_enabled')) {
            
                    $this->setOption($option->tag, $option->value);
                } else {
            
                    list($tag) = explode('_', $option->tag, 2);
                    $this->setEnabled($tag, $option->value);
                }   
            }
        }
    }
    
    private static function getModuleDir()
    {
        $dir = realpath(dirname(dirname(dirname(__file__))));
        
        if (basename($dir) != 'security') {
            throw new Security_Exception('Invalid directory structure');
        }
        return $dir;
    }
    
    public static function getIncludePaths($string = false)
    {
        $incPaths = explode(PATH_SEPARATOR, get_include_path());
        $returnPaths = array();
        
        $dir = self::getModuleDir();
        
        foreach (self::$_paths as $path) {
            
            if (!in_array($dir.$path, $incPaths)) {
                
                $returnPaths[] = $dir.$path;
            }
        }
        
        if (!empty($returnPaths)) {
            
            return ($string === true) ? implode(PATH_SEPARATOR, $returnPaths) : $returnPaths;
        }
        
        return null;
    }
    
    public static function getInstance()
    {
        if(null === self::$_instance) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public static function getAccountInstance() {
        $modelName = Security_System::getInstance()->getOption('accountModel');
        if (class_exists($modelName)) {
            
            if ($model = call_user_func($modelName.'::getInstance')) {
                return $model;
            }
        }
        return null;
    }
    
    public function isInstalled() {
        if ($this->_installed === true) {
            return true;
        }
        return false;
    }
    
    public function getOption($name)
    {
        if (array_key_exists($name, $this->_options)) {
            return $this->_options[$name];
        }
        return null;
    }
    
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;
    }
    
    public function isEnabled($name = 'system')
    {
        if ($this->isInstalled() && $this->_enabled['system']) {
            if (array_key_exists($name, $this->_enabled)) {
                return $this->_enabled[$name];
            }
        }
        return false;
    }
    
    public function setEnabled($name, $value = true)
    {
        $this->_enabled[$name] = (bool) $value;
    }
    
    public function setFront(Zend_Controller_Front $front)
    {
        $front->registerPlugin(new Security_Controller_Plugin_Loader());
        $this->_front = $front;
    }
    
    public function getLoadedModels() {
        return $this->_loadedModels;
    }
}