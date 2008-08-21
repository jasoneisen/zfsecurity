<?php

final class Security_System
{
    private static $_instance = null;
    
    private $_params =      array('activeModelName'             =>  null,
                                  'accountTableName'            =>  null,
                                  'identityColumnName'          =>  null,
                                  'identityColumnTitle'         =>  null,
                                  'credentialColumnName'        =>  null,
                                  'credentialColumnTitle'       =>  null,
                                  'credentialColumnTreatment'   =>  null,
                                  'useSecurityErrorController'  =>  true);
    
    private $_enabled =     array('system'    =>  false,
                                  'acl'       =>  false,
                                  'doctrine'  =>  false);
    
    private function __clone()
    {}
    
    private function __construct($params = null)
    {
        if (null === $params) {
            try {
                if (!$params = Doctrine::getTable('SecurityOption')->findAll()) {
                    return;
                }
            } catch (Exception $e) {
                return;
            }
        }
        
        if ($params instanceof Doctrine_Collection || $params instanceof Zend_Config) {
            
                $params = $params->toArray();
        }
            
        if (!is_array($params)) {
                
            throw new Security_Exception('Params must be sent as an array');
        }
        
        if (empty($params)) {
            return;
        }
        
        foreach ($params as $name => $value) {
            
            if (strstr($name, '_enabled')) {
                
                if ($value) {
                
                    list($tag) = explode('_', $name, 2);
                    $this->enable($tag);
                    
                }
                
            } else {
                
                $this->_params[$name] = (string) $value;
            }
        }
        
        // For BC
        if (Zend_Loader::isReadable('Security/Account/GroupLink.php')) {
            require_once 'Security/Account/GroupLink.php';
        }
    }
    
    public static function getInstance()
    {
        if (null === self::$_instance) {
            throw new Security_Exception('Cannot get instance before Security_System::start() has been called');
        }

        return self::$_instance;
    }
    
    public static function start($params = null)
    {
        if (null !== self::$_instance) {
            throw new Security_Exception('Security system has already been started');
        }
        
        self::$_instance = new Security_System($params);
    }
    
    public function isEnabled($name = 'system')
    {
        if ($this->_enabled['system']) {
            
            if (array_key_exists($name, $this->_enabled)) {
                
                return $this->_enabled[$name];
            }
        }
        return false;
    }
    
    public function enable($name)
    {
        switch ($name) {
            
            case 'system':
            case 'doctrine':
            break;
            
            case 'acl':
                
                $front = Zend_Controller_Front::getInstance();
                
                if (!$front->hasPlugin('Security_Controller_Plugin_Auth')) {
                    
                    $front->registerPlugin(new Security_Controller_Plugin_Auth());
                }
            break;
            
            default:
                throw new Security_Exception('Component \''. $name .'\' does not exist in Security_System');
            break;   
        }
        
        $this->_enabled[$name] = true;
    }
    
    public function disable($name)
    {
        switch ($name) {
            
            case 'system':
            case 'acl':
                
                $plugin = 'Security_Controller_Plugin_Auth';
                $front = Zend_Controller_Front::getInstance();
                
                if ($front->hasPlugin($plugin)) {

                    $front->unregisterPlugin($plugin);
                }
            break;
            
            case 'doctrine':
            break;
            
            default:
                throw new Security_Exception('Component \''. $name .'\' does not exist in Security_System');
            break;   
        }
        
        $this->_enabled[$name] = false;
    }
    
    //public static function getInstance($front = null)
    //{
    //    if (null === $front) {
    //        $front = Zend_Controller_Front::getInstance();
    //    }
    //    
    //    if (!$front instanceof Zend_Controller_Front) {
    //        throw new Security_Exception('Passed argument not instance of Zend_Controller_Front');
    //    }
    //    if (!$system = $front->getParam('SecuritySystem')) {
    //        throw new Security_Exception('Passed front controller does not have a Security_System set');
    //    }
    //    if (!$system instanceof Security_System) {
    //        throw new Security_Exception('Param stored in front not an instance of Security_System');
    //    }
    //    
    //    return $system;
    //}
    
    public static function getActiveModel() {
        
        $modelName = Security_System::getInstance()->getParam('activeModelName');
        
        if (class_exists($modelName)) {
            
            if ($model = call_user_func($modelName.'::getInstance')) {
                return $model;
            }
        }
        return null;
    }
    
    public function getParam($name)
    {
        if (array_key_exists($name, $this->_params)) {
            return $this->_params[$name];
        }
        return null;
    }
    
    public function getParams()
    {
        return $this->_params;
    }
    
    public function setParam($name, $value)
    {
        $name = (string) $name;
        $this->_params[$name] = $value;
        return $this;
    }
    
    public function setParams(array $params = array())
    {
        foreach ($params as $name => $value) {
            $this->setParam($name, $value);
        }
        return $this;
    }
}