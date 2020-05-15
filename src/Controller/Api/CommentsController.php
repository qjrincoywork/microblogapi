<?php
namespace App\Controller\Api;

use Cake\ORM\TableRegistry;
use Firebase\JWT\JWT;

class CommentsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
        $this->loadComponent('RequestHandler');
    }
    
    public function add()
    {
        $comment = $this->Comments->newEntity();
        $datum['success'] = false;
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), 
                                   $this->request->getData('api_key'), ['HS256']);
                                   
            $commentData = get_object_vars($request->data);
            $comment = $this->Comments->patchEntity($comment, $commentData);
            
            if(!$comment->getErrors()) {
                if ($this->Comments->save($comment)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($comment);
                $datum['errors'] = $errors;
            }
            
            return $this->jsonResponse($datum);
        }
    }
    
    public function edit()
    {
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'),
                                   $this->request->getData('api_key'), ['HS256']);
            $commentData = get_object_vars($request->data);
            $comment = $this->Comments->get($commentData['id']);
            $comment = $this->Comments->patchEntity($comment, $commentData);
            
            if(!$comment->getErrors()) {
                if ($this->Comments->save($comment)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($comment);
                $datum['errors'] = $errors;
            }
            
            return $this->jsonResponse($datum);
        }
    }
    
    public function userComment()
    {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $id = $request->data;
        $data = $this->Comments->get($id);
        return $this->jsonResponse($data);
    }
}