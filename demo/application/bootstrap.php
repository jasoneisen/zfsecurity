<?php

$basePath = dirname(dirname(__FILE__));
$ds = DIRECTORY_SEPARATOR;
$ps = PATH_SEPARATOR;

set_include_path(
    $basePath.$ds.'library'.$ps.
    $basePath.$ds.'library'.$ds.'doctrine'.$ps.
    $basePath.$ds.'application'.$ds.'models'.$ps.
    $basePath.$ds.'application'.$ds.'models'.$ds.'generated'.$ps.
    get_include_path());

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();