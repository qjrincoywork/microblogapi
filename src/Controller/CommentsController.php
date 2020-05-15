<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class CommentsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
    }
    
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->setLayout('main');
        if($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout(false);
        }
    }
    
    public function add($id)
    {
        $comment = $this->Comments->newEntity();
        if($this->request->is('post')) {
            $datum['success'] = false;
            $userId = $this->request->getSession()->read('Auth.User.id');
            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/comments/add.json', $postData);

            if(isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }
            
            return $this->jsonResponse($datum);
        }
        
        $post = $this->apiGateWay('/api/posts/userPost.json', $id);
        $data = get_object_vars($post);
        $this->set(compact('data', 'comment'));
    }
    
    public function edit($id)
    {
        $comment = $this->apiGateWay('/api/comments/userComment.json', $id);
        if($this->request->is(['post'])) {
            $datum['success'] = false;
            $userId = $this->request->getSession()->read('Auth.User.id');
            if($comment->user_id != $userId) {
                $datum['error'] = 'Unable to process action.';
                return $this->jsonResponse($datum);
            }

            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/comments/edit.json', $postData);
            
            if(isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }
            
            return $this->jsonResponse($datum);
        }
        $this->set(compact('comment'));
    }

    public function delete($id)
    {
        $comment = $this->Comments->get($id);
        
        if($this->request->is(['put', 'patch'])) {
            $datum['success'] = false;
            
            $id = $this->request->getSession()->read('Auth.User.id');
            $postData = $this->request->getData();
            $postData['deleted'] = 1;
            $postData['user_id'] = $id;
            
            $comment = $this->Comments->patchEntity($comment, $postData, ['validate' => 'Delete']);
            
            if($comment->getErrors()) {
                if(array_key_exists('id', $comment->getErrors())) {
                    $datum['error'] = $comment->getError('id.isMine');
                } else {
                    $errors = $this->formErrors($comment);
                    $datum['errors'] = $errors;
                }
            } else {
                if ($this->Comments->save($comment)) {
                    $datum['success'] = true;
                }
            }
            
            return $this->jsonResponse($datum);
        }
        $this->set(compact('comment'));
    }
}
