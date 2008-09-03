<?php

final class Security
{
    private static $_aclInstance = null;
    
    private static $_activeAccount = null;
    
    private static $_accountQuery = null;
    
    private static $_installed = null;
    
    private static $_initialized = false;
    
    private static $_params = array();
    
    private static $_plugins = array('Security_Controller_Plugin_Auth',
                                     'Security_Controller_Plugin_Router');
    
    public static function init($params = array())
    {
        if (true === self::$_initialized) {
            throw new Security_Exception("Security::init() has already been called");
        }
        
        self::$_initialized = true;
        
        if (empty($params) && Zend_Loader::isReadable('SecurityOption.php')) {
            try {
                $params = Doctrine_Query::create()
                    ->select('so.name, so.value')
                    ->from('SecurityOption so')
                    ->execute();
                
                foreach ($params as $param) {
                    self::setParam($param['name'], $param['value']);
                }
            } catch (Exception $e) {
                return;
            }
        }
        
        if ($params instanceof Zend_Config) {
            $params = $params->toArray();
        }
        
        if (is_array($params)) {
            self::setParams($params);
        }
        
        self::_registerPlugins();
        self::_addRoutes();
        self::_setAuthStorage();
    }
    
    public static function getAclInstance()
    {
        if (null === self::$_aclInstance) {
            self::$_aclInstance = new Security_Acl();
        }
        
        return self::$_aclInstance;
    }
    
    public static function getActiveAccount($force = false)
    {
        if (null === self::$_activeAccount ||
            true === $force) {
            
            self::$_activeAccount = new Doctrine_Null();
            
            if (self::isInstalled() &&
                $identity = Zend_Auth::getInstance()->getIdentity()) {

                $class = self::getParam('accountTableClass');
                $column = Doctrine::getTable($class)->getIdentifier();

                if (!empty($identity->$column)) {
                    
                    $record = self::getAccountQuery()
                        ->addWhere('a.'. $column .' = ?')
                        ->fetchOne(array($identity->$column));
                    
                    self::$_activeAccount = $record;
                }
            }
        }
        return self::$_activeAccount;
    }
    
    public static function setParam($name, $value)
    {
        $name = (string) $name;
        self::$_params[$name] = $value;
    }
    
    public static function setParams(array $params = array())
    {
        foreach ($params as $name => $value) {
            self::setParam($name, $value);
        }
    }
    
    public static function getParam($name)
    {
        if (isset(self::$_params[$name])) {
            return self::$_params[$name];
        }
        
        return null;
    }
    
    public static function getParams()
    {
        return self::$_params;
    }
    
    public static function getAccountQuery()
    {
        if (null === self::$_accountQuery) {
            $class = self::getParam('accountTableClass');
            self::$_accountQuery = Doctrine_Query::create()
                ->from($class .' a')
                ->leftJoin('a.Groups g');
        }
        
        return self::$_accountQuery;
    }
    
    public static function setAccountQuery(Doctrine_Query $query)
    {
        self::$_accountQuery = $query;
    }
    
    public static function isEnabled($name = 'system')
    {
        if (self::getParam('enableSystem')) {
            $return = self::getParam('enable' . ucfirst($name));
            return (null === $return) ? false : (bool) $return;
        }
        return false;
    }
    
    public static function isInstalled()
    {
        if (null === self::$_installed) {
            self::$_installed = false;
        
            if ($class = self::getParam('accountTableClass')) {
        
                try {
                    $identifier = Doctrine::getTable($class)->getIdentifier();
        
                    $query = Doctrine_Query::create()
                        ->select('a.'.$identifier)
                        ->from($class.' a')
                        ->leftJoin('a.Groups g')
                        ->limit(1)
                        ->execute();
        
                    self::$_installed = true;
                    
                } catch (Exception $e) {}
            }
        }
        return self::$_installed;
    }
    
    public static function isInitialized()
    {
        return self::$_initialized;
    }
    
    private static function _registerPlugins()
    {
        $front = Zend_Controller_Front::getInstance();
        foreach (self::$_plugins as $plugin) {
            if (!$front->hasPlugin($plugin)) {
                $front->registerPlugin(new $plugin());
            }
        }
    }
    
    private static function _setAuthStorage()
    {
        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session('Security_Auth'));
        
        if (($seconds = self::getParam('sessionExpiration')) && !empty($seconds)) {
            
            $authStorage = new Zend_Session_Namespace('Security_Auth');
    		$authStorage->setExpirationSeconds($seconds);
        }
    }
    
    private static function _addRoutes()
    {
		$routesPath = self::getParam('dataPath') . DIRECTORY_SEPARATOR . 'routes.xml';
		
		if (Zend_Loader::isReadable($routesPath)) {
		    
		    $router = Zend_Controller_Front::getInstance()->getRouter();
		    $routes = new Zend_Config_Xml($routesPath);
		    $router->addConfig($routes);
		}
    }
    
    public function __construct()
    {
        throw new Security_Exception('Security is static class. No instances can be created.');
    }
}