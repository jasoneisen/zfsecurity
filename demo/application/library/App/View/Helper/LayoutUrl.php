<?php

class App_View_Helper_LayoutUrl
{
    public function LayoutUrl($file = null)
    {
        // Get baseUrl
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

        // Remove trailing slashes
        $file = ($file !== null) ? ltrim($file, '/\\') : null;
        
        // Get layout
        $layout = Zend_Layout::getMvcInstance()->getLayout();

        // Build return
        $return = rtrim($baseUrl, '/\\') . '/styles/' . $layout . (($file !== null) ? ('/' . $file) : '');
        return $return;
    }
}