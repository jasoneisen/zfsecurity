<?php

class Security_AccountsController extends Security_Controller_Action_Backend
{
    public function init()
    {
        $this->view->columnTitle = $this->_getOption('identityColumnTitle');
        $this->view->columnName = $this->_getOption('identityColumnName');
    }
    
    public function indexAction()
    {
        $this->view->accounts = Doctrine_Query::create()
                                                ->from($this->_getOption('accountTableName') .' a')
                                                ->orderby('a.'.$this->_getOption('identityColumnName'))
                                                ->execute();
    }
    
    public function showAction()
    {
       $this->view->account = $this->_getAccount($this->_getParam('id'));
    }
    
    public function newAction()
    {
       $this->view->form = $this->_getForm('post');
    }
    
    public function createAction()
    {
        $form = $this->_getForm('post');
        
        if ($form->isValid($this->getRequest()->getPost())) {
        
            if ($account = $this->_saveAccount($form->getValues())) {
            
                $this->getHelper('Redirector')->gotoRoute(array('id'=>current($account->identifier())), 'security_account_path');
            }
        }
        
        $this->_setForm($form);
        $this->_forward('new');
    }
    
    public function editAction()
    {
       $this->view->form = $this->_getForm('put');
    }
    
    public function updateAction()
    {
       $form = $this->_getForm('put');
        
        if ($form->isValid($this->getRequest()->getPost())) {
        
            if ($account = $this->_saveAccount($form->getValues(), $this->getRequest()->getParam('id'))) {
            
                $this->getHelper('Redirector')->gotoRoute(array('id'=>current($account->identifier())), 'edit_security_account_path');
            }
        }
        
        $this->_setForm($form);
        $this->_forward('edit');
    }
    
    public function destroyAction()
    {
       
    }
    
    protected function _generateForm()
    {
        $identifier = Doctrine::getTable($this->_getOption('accountTableName'))->getIdentifier();
        $identityColumn = $this->_getOption('identityColumnName');
                                
        $form = new Security_Form_Account();
        
        if (!$this->getRequest()->isPost()
                && $account = $this->_getAccount($this->getRequest()->getParam('id'))) {
            
            $form->getSubForm('account')->getElement('identity')->setValue($account->{$identityColumn});
            $groups = Security_Acl::getInstance()->getGroups();
            
            $populate = array();
            
            foreach ($groups as $group) {
                
                if (isset($account->Groups[$group->id])) {
                    
                    $populate[$group->id] = true;
                }
            }
            
            $form->populate(array('groups'=>$populate));
        }
        
        return $form;
    }
    
    protected function _saveAccount($data, $account = null)
    {
        $tableName = $this->_getOption('accountTableName');
        $identityColumnName = $this->_getOption('identityColumnName');
        $credentialColumnName = $this->_getOption('credentialColumnName');
        $accountIdentifier = Doctrine::getTable($tableName)->getIdentifier();
        
        $groupLink = 'Group' . $tableName;
        $localColumn = ($accountIdentifier == 'id') ? strtolower($tableName) .'_id' : $accountIdentifier;
        
        if ($account === null) {
            
            $account = new Account();
            
        } elseif ($account instanceof Doctrine_Record
                 || (is_numeric($account) && $account = $this->_getAccount($account))) {
            
            $account->unlink('Groups');
            $account->save();
            
        } else {
            return false;
        }
        
        $query = Doctrine_Query::create()
                                 ->update($tableName.' a')
                                 ->set('a.'. $identityColumnName, '?', $data['account']['identity'])
                                 ->addWhere('a.'. $accountIdentifier .' = ?', current($account->identifier()));
        
        if (isset($account->{$credentialColumnName})) {
            
            if ($treatment = $this->_getOption('accountCredentialTreatment')) {
                
                $query->set('a.'. $credentialColumnName, $treatment, $data['account']['credential']);
            } else {
                
                $query->set('a.'. $credentialColumnName, '?', $data['account']['credential']);
            }
        }
        
        $query->execute();
        
        if (!empty($data['groups'])) {

            $account->link('Groups', array_keys(array_filter($data['groups'])));
        }
        
        $account->refresh();
        
        return $account;
    }
    
    protected function _getAccount($id)
    {
        $identifier = Doctrine::getTable($this->_getOption('accountTableName'))->getIdentifier();
        $tableName = $this->_getOption('accountTableName');
        
        return Doctrine_Query::create()
                               ->from($tableName .' a')
                               ->leftJoin('a.Groups g INDEXBY g.id')
                               ->addWhere('a.'. $identifier .' = ?')
                               ->fetchOne(array($id));
    }
}