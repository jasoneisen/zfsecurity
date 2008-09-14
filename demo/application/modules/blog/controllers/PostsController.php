<?php

class Blog_PostsController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $query = Doctrine_Query::create()
            ->select('p.id, p.title, p.body, p.created_at, a.firstname, a.lastname')
            ->from('Post p')
            ->innerJoin('p.Author a')
            ->orderby('p.created_at DESC');
        
        $this->view->posts = $query->execute();
    }
    
    public function showAction()
    {
        $postId = $this->getRequest()->getParam('id');
        
        $post = Doctrine_Query::create()
            ->select('p.id, p.title, p.body, p.created_at, a.firstname, a.lastname')
            ->from('Post p')
            ->innerJoin('p.Author a')
            ->leftJoin('p.Comments c')
            ->leftJoin('c.Author ca')
            ->addWhere('p.id = ?')
            ->orderby('p.created_at DESC')
            ->fetchOne(array($postId));
        
        if ($post) {
            
            $this->view->post = $post;
        }
    }
    
    public function newAction()
    {
        $generator = new App_Form_Doctrine();
        $form = $generator->generateFromTable('Post');
        
        $form->addElement('hidden', '_method', array('value' => 'post'));
        
        $this->view->form = $form;
    }
    
    public function createAction()
    {
        $generator = new App_Form_Doctrine();
        $form = $generator->generateFromTable('Post');
        
        $post = new Post();
        
        if (($form->isValid($this->getRequest()->getPost())) && $form->save($post)) {
            
            $this->_helper->_redirector->setGotoRoute(array('id'=>$post->id), 'blog_post_path');
            
        } else {
            
            $form->addElement('hidden', '_method', array('value' => 'post'));
            $this->view->form = $form;
            $this->render('new');
        }
    }
    
    public function editAction()
    {
        $postId = $this->getRequest()->getParam('id');
        
        if (!$post = Doctrine::getTable('Post')->find($postId)) {
            
            return;
        }
        
        $generator = new App_Form_Doctrine();
        $form = $generator->generateFromRecord($post);
        
        $form->addElement('hidden', '_method', array('value' => 'put'));
        
        $this->view->form = $form;
    }
    
    public function updateAction()
    {
        $postId = $this->getRequest()->getParam('id');
        
        if (!$post = Doctrine::getTable('Post')->find($postId)) {
        
            $this->render('edit');
            return;
        }
        
        $generator = new App_Form_Doctrine();
        $form = $generator->generateFromRecord($post);
        
        if (($form->isValid($this->getRequest()->getPost())) && $form->save($post)) {
            
            $this->_helper->_redirector->setGotoRoute(array('id'=>$post->id), 'blog_post_path');
            
        } else {
            
            $form->addElement('hidden', '_method', array('value' => 'put'));
            $this->view->form = $form;
            $this->render('edit');
        }
    }
    
    public function deleteAction()
    {
        $postId = $this->getRequest()->getParam('id');
        
        if ($post = Doctrine::getTable('Post')->find($postId)) {
        
            $this->view->post = $post;
        }
    }
    
    public function destroyAction()
    {
         $postId = $this->getRequest()->getParam('id');

         if ($post = Doctrine::getTable('Post')->find($postId)) {
         
             $post->delete();
             
             $this->_helper->_redirector->setGotoRoute(array(), 'blog_posts_path');
             
         } else {
             
             $this->view->error = "Unable to delete blog post";
             $this->render('delete');
         }
    }
}