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
    
    public function testAction()
    {
        
        
        $bldr = new Doctrine_Import_Builder();
        
        $definition = array(
            'className'     =>  'GroupUser',
            'tableName'     =>  'security_group_user',
            'columns'       =>  array(
                'group_id'  =>  array(
                    'unsigned'          =>  true,
                    'primary'           =>  true,
                    'notnull'           =>  true,
                    'autoincrement'     =>  false,
                    'type'              =>  'integer',
                    'length'            =>  4),
                'user_id'   =>  array(
                    'unsigned'          =>  true,
                    'primary'           =>  true,
                    'notnull'           =>  true,
                    'autoincrement'     =>  false,
                    'type'              =>  'integer',
                    'length'            =>  4),
                ),
            'relations'     =>  array(
                array(
                    'class'         =>  'Group',
                    'alias'         =>  'Groups',
                    'type'          =>  Doctrine_Relation::ONE,
                    'local'         =>  'group_id',
                    'foreign'       =>  'id',
                    'onDelete'      =>  'CASCADE',
                    'onUpdate'      =>  'CASCADE'),
                array(
                    'class'         =>  'User',
                    'alias'         =>  'Users',
                    'type'          =>  Doctrine_Relation::ONE,
                    'local'         =>  'user_id',
                    'foreign'       =>  'user_id',
                    'onDelete'      =>  'CASCADE',
                    'onUpdate'      =>  'CASCADE')),
            'options'       =>  array(
                'type'      =>  '',
                'charset'   =>  '',
                'collation' =>  '',
                'collate'   =>  ''
            ),
        );
        
        // Outputs what would normally go in Base*.php
        $gen = $bldr->buildDefinition($definition);
        
        eval($gen);
        
        
        
        Doctrine::getTable('User')->getRecordInstance()->hasMany('Group as Groups', array(
			'local'		=>	'user_id',
			'foreign'	=>	'group_id',
			'refClass'	=>	'GroupUser'));
        
        //die($gen);
        //eval("class GroupUser extends Doctrine_Record {}");
        //
        //$groupUser = new GroupUser();
        //
        //$groupUser->setTableName('security_group_user');
        //
        //$groupUser->hasColumn('group_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //        
        //$groupUser->hasColumn('user_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //
        //$groupUser->hasOne('Group', array(
        //        'local'     =>  'group_id',
        //        'foreign'   =>  'id',
        //        'onDelete'  =>  'CASCADE',
        //        'onUpdate'  =>  'CASCADE'));
        //
        //$groupUser->hasOne('User', array(
        //        'local'     =>  'user_id',
        //        'foreign'   =>  'id',
        //        'onDelete'  =>  'CASCADE',
        //        'onUpdate'  =>  'CASCADE'));
        //
        //
        //die(print_r(Doctrine::getTable('GroupUser')->getExportableFormat()));
        //Doctrine::getTable('GroupUser')->removeColumn('id');
        //Doctrine::getTable('GroupUser')->initDefinition();
        //print_r(Doctrine::getTable('GroupUser')->getIdentifier());
        //die();
        $u = Doctrine::getTable('User')->findOneByUserId(1);
        $u->loadReference('Groups');
        die(print_r($u->toArray(true)));
        
        echo "getTable:\n";
        $gu = Doctrine::getTable('GroupUser')->findOneByUserId(1);
        $gu->loadReference('Groups');
        $gu->loadReference('Users');
        print_r($gu->identifier());
        print_r($gu->toArray());
        die();
        echo "Doctrine_Query:\n";
        $gu = Doctrine_Query::create()->from('GroupUser')->addWhere('GroupUser.user_id = ?')->fetchOne(array(1));
        print_r($gu->identifier());
        
        echo "new GroupUser():\n";
        $gu = new GroupUser();
        
        //print_r($user->identifier());
        //print_r($user->Groups->toArray());
        
        //die(print_r($groupUser->getReferences()));
        
        //print_r($groupUser->toArray());
        print_r(Doctrine::getTable('GroupUser')->getColumns());
        print_r(Doctrine::getTable('User')->getRelations());
        //die();
        
        //$conn = Doctrine::getConnectionByTableName('User');
        //
        //$table = new Doctrine_Table('GroupUser', $conn);
        //
        //$table->setColumn('group_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //
        //$table->setColumn('user_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //
        //$table->getRelationParser()->bind('Group', array(
        //    'type'  =>  Doctrine_Relation::ONE,
        //    'local'     =>  'group_id',
        //    'foreign'   =>  'id',
        //    'onDelete'  =>  'CASCADE',
        //    'onUpdate'  =>  'CASCADE'));
        //
        //$table->getRelationParser()->bind('User', array(
        //    'type'  =>  Doctrine_Relation::ONE,
        //    'local'     =>  'user_id',
        //    'foreign'   =>  'id',
        //    'onDelete'  =>  'CASCADE',
        //    'onUpdate'  =>  'CASCADE'));
        //
        //$table->initDefinition();
        //$table->initIdentifier();
        //
        //$conn->addTable($table);
        //
        //print_r(Doctrine::getTable('GroupUser')->getColumns());
        
        
        //$groupUser = new GroupUser($table);
        
        //print_r($table->getData());
        //echo $table->getComponentName();
        
        
        
        //die(var_dump($g->User));
        //$nTable = Doctrine::getTable('GroupUser');
        //$record = new Doctrine_Record($table);
        //die(var_dump($res = Doctrine::getTable('GroupUser')->findOneByGroupId(2)));
        //print_r($res->toArray());
        //print_r($testTable = Doctrine::getTable('GroupUser')->getColumns());
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
    
    protected function _generateAcl()
    {
        $gen = new Security_Acl_Generator();
        foreach ($gen->getResources() as $module => $resources) {
            foreach ($resources as $resource) {
                foreach ($gen->getActions($resource) as $action) {
                    echo $module ." ". $resource ." ". $action ."<br>";
                }
            }
        }
    }
    
    public function updateAclAction()
    {
        $gen = new Security_Acl_Generator();
        
        if (!$this->getRequest()->isPost()) {
            
            $modules = array();
            
            foreach ($gen->getResources() as $genModule => $genResources) {
                
                foreach ($genResources as $genResource) {
                    
                    foreach ($gen->getActions($genResource) as $genAction) {
                        
                        if (!$this->_aclExists($genModule, $genResource, $genAction)) {

                            $modules[$genModule]['resources'][$genResource]['privileges'][$genAction]['new'] = true;
                        }
                    }
                }
            }
            
            if (!empty($modules)) {
                $this->view->acl = $modules;
            }
        } else {
            
            $parts = Doctrine_Query::create()
                                     ->select('ap.name')
                                     ->from('AclPart ap INDEXBY ap.name')
                                     ->execute()
                                     ->toArray();
            
            foreach ($gen->getResources() as $genModule => $genResources) {
                
                $module = $this->_addPart($genModule);
                
                foreach ($genResources as $genResource) {
                    
                    $resource = $this->_addPart($genResource);
                    
                    foreach ($gen->getActions($genResource) as $genAction) {
                        
                        $privilege = $this->_addPart($genAction);
                        
                        if (!$this->_aclExists($module->name, $resource->name, $privilege->name)) {
                           
                           $acl = new Acl();
                           $acl->module_id = $module->id;
                           $acl->resource_id = $resource->id;
                           $acl->privilege_id = $privilege->id;
                           $acl->save();
                        }
                    }
                }
            }
        }
    }
    
    protected function _addPart($name)
    {
        if (!isset($this->_parts[$name])) {
            if (!$aclPart = Doctrine::getTable('AclPart')->findOneByName($name)) {
                $aclPart = new AclPart();
                $aclPart->name = $name;
                $aclPart->save();
            }
            $this->_parts[$name] = $aclPart;
        }
        return $this->_parts[$name];
    }
    
    protected function _aclExists($module, $resource, $privilege)
    {
        try {
            Doctrine::getTable('Acl');
        } catch (Doctrine_Exception $e) {
            return;
        }
        // This could be time tested against looping through Security_Acl::getInstance->getAcl()
        return (Doctrine_Query::create()
                                ->from('Acl a')
                                ->innerJoin('a.Module m')
                                ->innerJoin('a.Resource r')
                                ->innerJoin('a.Privilege p')
                                ->addWhere('m.name = ?')
                                ->addWhere('r.name = ?')
                                ->addWhere('p.name = ?')
                                ->fetchOne(array($module, $resource, $privilege))) ? true : false;
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