<?php

/**
 * Blah
 *
 * @license New BSD License
 * @author Jason Eisenmenger <jasoneisen at gmail>
 **/
abstract class Security_Controller_Action_Backend extends Zend_Controller_Action
{
    /**
     * @var string $_formNamespace              Where forms are stored between _forward()'s
     */
    protected $_formNamespace = 'securityFormNamespace';
    
    protected function _secParam($name) {
        
        return Security::getParam($name);
    }
    
    public function init()
    {
        // Adds partial path to the view if it hasn't been already
        $view = Zend_Layout::getMvcInstance()->getView();
        
        foreach ($view->getScriptPaths() as $path) {
            
            if (false !== strpos($path, 'security/views/scripts/')) {
                
                $addPath = dirname($path) .'/partials/';
                
            } elseif (false !== strpos($path, 'security/views/partials/')) {
                
                $dontSet = true;
            }
        }
        
        if (!isset($dontSet) && isset($addPath)) {
            
            $view->addScriptPath($addPath);
        }
        
        if (!Security::isInstalled()) {
            $this->_forward('not-installed');
            return;
        }
        
        $front = Zend_Controller_Front::getInstance();
        $request = $this->getRequest();
        
        if ($request->getActionName() == $front->getDefaultAction() && $request->getActionName() != 'index') {
            $this->_forward('index');
        }
    }
    
    public function notInstalledAction()
    {
        $this->renderScript('partials/not-installed.phtml');
    }
    
    /**
     * Helper function to get the current form, or call _generateForm() if it has not been set
     * Also sets the form action for the rest controller plugin to use
     *
     * @param string $method                    put, post, or delete
     * @return Zend_Form Object
     */
    protected function _getForm($method = null)
    {
        if (!Zend_Registry::isRegistered($this->_formNamespace)) {
            
            $form = $this->_generateForm();
            
            if ($method !== null) {
                if ($form->getElement('_method')) {
                    $form->getElement('_method')->setValue($method);
                }
            }
            
            $this->_setForm($form);
        }
        return Zend_Registry::get($this->_formNamespace);
    }
    
    /**
     * Helper method to save a form for later use by another action
     *
     * @return void
     */
    protected function _setForm($form)
    {
        Zend_Registry::set($this->_formNamespace, $form);
    }
    
    /**
     * Must be implemented by the extending class
     *
     * @return Zend_Form Object
     */
    abstract protected function _generateForm();
}