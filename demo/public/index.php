<?php

require_once '..' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'bootstrap.php';

Zend_Controller_Front::getInstance()->dispatch();