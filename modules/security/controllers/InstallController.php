<?php

class Security_InstallController extends Security_Controller_Action_Backend
{
    protected $_parts = array();
    
    public function init()
    {
        parent::init();
        
        // Set expiration to 5 minutes
        $session = new Zend_Session_Namespace('SecurityInstall');
        $session->setExpirationSeconds(600);
        
        if (!isset($session->exists)) {
            
            // Session expired or didn't exist, start at index if not already
            $front = Zend_Controller_Front::getInstance();
            $request = $this->getRequest();

            if ($request->getActionName() != $front->getDefaultAction()) {
                
                $session->exists = true;
                $this->_forward('index');
            }
        }
        
        $session->exists = true;
        
        
        
        //if (!$this->getRequest()->isPost()) {
        //    
        //    try {
        //        
        //        if (Security_System::getInstance()->isInstalled()) {
        //        
        //            $this->getHelper('Redirector')->gotoRoute(array('module'=>'security','controller'=>'update'), 'default');
        //        
        //        }
        //    } catch (Exception $e) {}
        //}
    }
    
    public function indexAction()
    {
        // Intro
    }
    
    public function stepOneAction()
    {
        // Check if bootstrap is setup properly
        
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->bootstrapIsSetup()) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-two'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $this->view->form = $this->_getForm();
    }
    
    public function stepTwoAction()
    {
        // Check DB privileges
        $migrationPath = dirname(dirname(__FILE__)) . '/data/migrations';
        
        $form = $this->_getForm();
        $form->addElement('text', 'migrationPath', array(
            'label' => 'Migrations path',
            'size' => strlen($migrationPath) + 5,
            'required' => true,
            'value' => $migrationPath));
        
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->hasRequiredDbAccess($form->getValue('migrationPath'))) {
                
                $this->_setSession('migrationPath', $form->getValue('migrationPath'));
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-three'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $this->view->form = $this->_getForm();
    }
    
    public function stepThreeAction()
    {
        // Generate Models
        $modelPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/models';
        $schemaPath = dirname(dirname(__FILE__)) . '/data/schema.yml';
        
        $form = $this->_getForm();
        $form->addElement('text', 'accountTable', array('label' => 'Account table class', 'required' => true));
        $form->addElement('text', 'accountTableAlias', array('label' => 'Account table plural alias', 'required' => true));
        
        $form->addElement('text', 'modelPath', array(
            'label' => 'Model path',
            'size' => strlen($modelPath) + 5,
            'required' => true,
            'value' => $modelPath));
        
        $form->addElement('text', 'schemaPath', array(
            'label' => 'Schema path',
            'size' => strlen($schemaPath) + 5,
            'required' => true,
            'value' => $schemaPath));
        
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            
            $install = new Security_Install();
            
            if ($install->generateModels($form->getValue('accountTable'), $form->getValue('accountTableAlias'), $modelPath, $schemaPath)) {
                
                $this->_setSession('accountTable', $form->getValue('accountTable'));
                $this->_setSession('accountTableAlias', $form->getValue('accountTableAlias'));
                $this->_setSession('modelPath', $form->getValue('modelPath'));
                $this->_setSession('schemaPath', $form->getValue('schemaPath'));
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-four'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $this->view->form = $form;
    }
    
    public function stepFourAction()
    {
        // Add relation to user table
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->hasGroupsRelation($this->_getSession('accountTable'))) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-five'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $this->view->form = $this->_getForm();
    }
    
    public function stepFiveAction()
    {
        // Generate and execute SQL from models
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->executeSqlFromModels($this->_getSession('accountTable'), $this->_getSession('migrationPath'))) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-six'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $this->view->form = $this->_getForm();
    }
    
    public function finishedAction()
    {
        // Done, send to /security/update to update acl list
    }
    
    public function justDoItAction()
    {
        $secSys = Security_System::getInstance();
        
        //if ($secSys->isInstalled()) {
        //    $this->_redirect('/security');
        //}
        
        $exporter = new Doctrine_Export();
        
        $tables = array_values(array_merge(Doctrine::loadModels($secSys->getDir('models'), Doctrine::MODEL_LOADING_CONSERVATIVE),
                              array('Group'.$secSys->getOption('accountTableClass'))));
        
        if ($queries = $exporter->exportSortedClassesSql($tables, false)) {
            
            $conn = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
            
            foreach ($queries as $query) {
                    
                $conn->exec($query);
            }
            
            $conn->exec("INSERT INTO `security_option` VALUES ('acl_enabled', 'ACL System', '0', 'Enables/Disables ACL')");
            $conn->exec("INSERT INTO `security_option` VALUES ('system_enabled', 'Security System', '0', 'Enables/Disables the entire system.  This overrides all other enabled values.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('useSecurityErrorController', 'Security Error Controller', '1', 'Enables/Disables the use of the Security module''s error controller for security restrictions.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('activeModelClass', 'Active Model Name', 'Security_User', 'The name of the model used with your online user.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('accountTableClass', 'Account Table Name', 'User', 'Database table where your accounts are stored.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('loginIdentityColumn', 'Identity Column Name', 'user_email', 'Doctrine aliased column name for identity column. Used to authorize logins.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('loginIdentityLabel', 'Identity Column Title', 'Email Address', 'Title to give the identity column.  For use in forms/views.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('loginCredentialColumn', 'Credential Column Name', 'user_password', 'Doctrine aliased column name for credential column. Used to authorize logins.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('loginCredentialLabel', 'Credential Column Title', 'Password', 'Title to give the credential column.  For use in forms/views.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('loginCredentialTreatment', 'Credential Column Treatment', 'md5(?)', 'Treatment for the credential column during authorization.')");
        }
        
        $this->_redirect('/security');
    }
    
    protected function _generateForm()
    {
        $form = new Zend_Form();
        $form->addElement('submit', 'submit', array('label' => 'Next', 'order' => 100));
        return $form;
    }
    
    protected function _getSession($name)
    {
        $session = new Zend_Session_Namespace('SecurityInstall');
        if (isset($session->{$name})) {
            return $session->{$name};
        }
        return null;
    }
    
    protected function _setSession($name, $value)
    {
        $session = new Zend_Session_Namespace('SecurityInstall');
        $session->{$name} = $value;
    }
}