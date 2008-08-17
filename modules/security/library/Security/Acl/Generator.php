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
        $controllerClass = "";
        
        // Create the class name
        if (strstr($resource, '_')) {
            $pieces = explode('_', $resource);
            foreach ($pieces as $piece) {
                if (strtolower($piece) != 'controller') {
                    $controllerClass .= ucfirst(strtolower($piece)) .'_';
                }
            }
            $controllerClass = substr($controllerClass, 0, strlen($controllerClass)-1) . 'Controller';
        } else {
            $controllerClass = ucwords(strtolower($resource)) . 'Controller';
        }
        
        $controllerFile = null;
        
        //Find the controller file
        $fc = Zend_Controller_Front::getInstance();
        foreach ($fc->getControllerDirectory() as $dir_path)
        {
            $test_file = $dir_path . '/' . $controllerClass . '.php';
            if (file_exists($test_file))
            {
                $module = basename(dirname($dir_path));
                $controllerFile = $test_file;
                break;
            }
        }
        
        if (empty($controllerFile))
            return false;
        
        //echo "<u>".ucfirst($module).$controllerClass."</u><br>";
        // Inspect the controller class for methods
        require_once($controllerFile);
        
        if ($module == $fc->getDefaultModule() || $module == 'application') {
            if ($fc->getParam('prefixDefaultModule')) {
                $className = ucfirst($fc->getDefaultModule()).'_'.$controllerClass;
            } else {
                $className = $controllerClass;
            }
        } else {
            $className = ucfirst($module).'_'.$controllerClass;
        }
        
        $reflect = new ReflectionClass($className);
        $actions = array();
        
        foreach ($reflect->getMethods() as $method)
        {
            // Find the action methods
            if (preg_match('/^(\w+)Action$/', $method->name, $matches))
                $actions[] = $matches[1];
        }
        return $actions;
    }
}