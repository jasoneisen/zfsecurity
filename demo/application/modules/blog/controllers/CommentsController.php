<?php

class Blog_CommentsController extends Zend_Controller_Action
{
    public function newAction()
    {
        $postId = $this->getRequest()->getParam('post_id');
        
        $generator = new App_Form_Doctrine();
        $generator->ignoreRelation('Post');
        $form = $generator->generateFromTable('Comment');
        
        $form->addElement('hidden', '_method', array('value' => 'post'));
        $form->setAction($this->view->Url(array('post_id'=>$postId), 'new_blog_post_comment_path'));
        
        $this->view->form = $form;
    }
    
    public function createAction()
    {
        $postId = $this->getRequest()->getParam('post_id');
        
        $generator = new App_Form_Doctrine();
        $generator->ignoreRelation('Post');
        $form = $generator->generateFromTable('Comment');
        
        $comment = new Comment();
        $comment->post_id = $postId;
        
        if (($form->isValid($this->getRequest()->getPost())) && $form->save($comment)) {
            
            $this->_helper->_redirector->setGotoRoute(array('id'=>$postId), 'blog_post_path');
            
        } else {
            
            $form->addElement('hidden', '_method', array('value' => 'post'));
            $this->view->form = $form;
            $this->render('new');
        }
    }
    
    public function editAction()
    {
        $postId = $this->getRequest()->getParam('post_id');
        $commentId = $this->getRequest()->getParam('id');
        
        if (!$comment = Doctrine::getTable('Comment')->find($commentId)) {
            
            return;
        }
        
        $generator = new App_Form_Doctrine();
        $generator->ignoreRelation('Post');
        $form = $generator->generateFromRecord($comment);
        
        $form->addElement('hidden', '_method', array('value' => 'put'));
        
        $this->view->form = $form;
    }
    
    public function updateAction()
    {
        $postId = $this->getRequest()->getParam('post_id');
        $commentId = $this->getRequest()->getParam('id');
        
        if (!$comment = Doctrine::getTable('Comment')->find($commentId)) {
            
            return;
        }
        
        $generator = new App_Form_Doctrine();
        $generator->ignoreRelation('Post');
        $form = $generator->generateFromRecord($comment);
        
        if (($form->isValid($this->getRequest()->getPost())) && $form->save($comment)) {
            
            $this->_helper->_redirector->setGotoRoute(array('id'=>$postId), 'blog_post_path');
            
        } else {
            
            $form->addElement('hidden', '_method', array('value' => 'put'));
            $this->view->form = $form;
            $this->render('edit');
        }
    }
    
    public function deleteAction()
    {
        $postId = $this->getRequest()->getParam('post_id');
        $commentId = $this->getRequest()->getParam('id');
        
        if ($comment = Doctrine::getTable('Comment')->find($commentId)) {
        
            $this->view->comment = $comment;
        }
    }
    
    public function destroyAction()
    {
         $postId = $this->getRequest()->getParam('post_id');
         $commentId = $this->getRequest()->getParam('id');

         if ($comment = Doctrine::getTable('Comment')->find($commentId)) {
         
             $comment->delete();
             
             $this->_helper->_redirector->setGotoRoute(array('id'=>$postId), 'blog_post_path');
             
         } else {
             
             $this->view->error = "Unable to delete comment";
             $this->render('delete');
         }
    }
}