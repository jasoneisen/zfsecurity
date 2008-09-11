<?php

$ps = PATH_SEPARATOR;
$ds = DIRECTORY_SEPARATOR;
$basePath = dirname(dirname(__FILE__)) . $ds;

set_include_path(
    $basePath.'library'.$ps.
    $basePath.'library'.$ds.'doctrine'.$ps.
    $basePath.'application'.$ds.'library'.$ps.
    $basePath.'application'.$ds.'models'.$ps.
    $basePath.'application'.$ds.'models'.$ds.'generated'.$ps.
  $basePath.'application'.$ds.'modules'.$ds.'security'.$ds.'library'.$ps.
    get_include_path());

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

$config = new Zend_Config_Xml($basePath.'application'.$ds.'config'.$ds.'config.xml');

$profiler = new Doctrine_Connection_Profiler();
Zend_Registry::set('Doctrine_Connection_Profiler', $profiler);

Doctrine_Manager::connection($config->database->string)
    ->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, true)
    ->setAttribute(Doctrine::ATTR_AUTO_ACCESSOR_OVERRIDE, true)
	->setListener($profiler);

$manager = Doctrine_Manager::getInstance();
$manager->setAttribute('use_dql_callbacks', true);
$manager->setAttribute('model_loading', 'conservative');

$frontController = Zend_Controller_Front::getInstance();
$frontController->addModuleDirectory($basePath.'application'.$ds.'modules')
    ->setDefaultModule('default');

$router = new Zend_Controller_Router_Rewrite();
$router->addDefaultRoutes();

$frontController->setRouter($router);

Zend_Layout::StartMvc();

$layout = Zend_Layout::getMvcInstance();
$layout->setLayoutPath($basePath.'application'.$ds.'layouts');
$layout->getInflector()->setTarget(':script/layout.:suffix');
$layout->setLayout('default');

$view = $layout->getView();
$view->addScriptPath($basePath.'application'.$ds.'layouts'.$ds.'default');
$view->addHelperPath($basePath.'application'.$ds.'library'.$ds.'App'.$ds.'View'.$ds.'Helper', 'App_View_Helper');
$view->headTitle($config->layout->title)->setSeparator(' / ');
$view->placeholder('branding')->set($config->layout->branding);

Zend_Dojo::enableView($view);

 Security::init();