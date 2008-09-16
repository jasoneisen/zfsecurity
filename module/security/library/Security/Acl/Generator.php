<?php

class Security_Acl_Generator
{
    public function getResources() {
        
        $fc = Zend_Controller_Front::getInstance();
        $resources = array();
        
        foreach ($fc->getControllerDirectory() as $dir_path)
        {
            if (basename(dirname($dir_path)) == 'default' || basename(dirname($dir_path)) == 'application') {
                
                $module = 'default';
            } else {
                
                $module = basename(dirname($dir_path));
            }
            
            $dir = dir($dir_path);
            
            while ($file = $dir->read())
            {
                if (preg_match('/^([A-Z][a-z]+)Controller\.php$/', $file, $matches))
                    $resources[$module][] = strtolower($matches[1]);
            }
            $dir->close();
        }
        return $resources;
    }
    
    public function getActions($resource) {
        
        if (strstr($resource, '_')) {
            
            list($moduleName, $controllerName) = explode('_', $resource);
            $controllerName = ucfirst($controllerName) . 'Controller';
            $controllerClass = ucfirst($moduleName) .'_'. $controllerName;
            
        } else {
            
            $controllerName = ucfirst($resource . 'Controller');
            $controllerClass = $controllerName;
        }
        
        $fc = Zend_Controller_Front::getInstance();
        $dirs = $fc->getControllerDirectory();
        
        $dir = isset($moduleName) ? $dirs[$moduleName] : $dirs[$fc->getDefaultModule()];
        $controllerFile =  $dir .'/'. $controllerName . '.php';
        
        require_once $controllerFile;
        
        //if ($module == $fc->getDefaultModule() || $module == 'application') {
        //    if ($fc->getParam('prefixDefaultModule')) {
        //        $className = ucfirst($fc->getDefaultModule()).'_'.$controllerClass;
        //    } else {
        //        $className = $controllerClass;
        //    }
        //} else {
        //    $className = ucfirst($module).'_'.$controllerClass;
        //}
        $className = $controllerClass;
        
        $reflect = new ReflectionClass($className);
        $actions = array();
        
        $camelCase = new Zend_Filter_Word_CamelCaseToDash();
        
        foreach ($reflect->getMethods() as $method)
        {
            // Find the action methods
            if (preg_match('/^(\w+)Action$/', $method->name, $matches))
                $actions[] = strtolower($camelCase->filter($matches[1]));
        }
        return $actions;
    }
}