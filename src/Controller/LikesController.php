<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class LikesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
    }
    
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->setLayout('main');
        if($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout(false);
        }
    }
    
    public function add($postId) {
        $userId = $this->request->getSession()->read('Auth.User.id');
        $postData['post_id'] = $postId;
        $postData['user_id'] = $userId;
        $result = $this->apiGateWay('/api/likes/add.json', $postData);
        
        if(isset($result->success) && $result->success) {
            $datum['success'] = $result->success;
        } else {
            $datum = get_object_vars($result);
        }
        
        return $this->jsonResponse($datum);
    }
    
    public function delete($postId) {
        $userId = $this->request->getSession()->read('Auth.User.id');
        $postData['post_id'] = $postId;
        $postData['user_id'] = $userId;
        $result = $this->apiGateWay('/api/likes/delete.json', $postData);
        
        if(isset($result->success) && $result->success) {
            $datum['success'] = $result->success;
        } else {
            $datum = get_object_vars($result);
        }
        return $this->jsonResponse($datum);
    }
}
