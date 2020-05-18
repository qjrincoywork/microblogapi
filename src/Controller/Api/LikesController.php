<?php
namespace App\Controller\Api;

use Cake\ORM\TableRegistry;
use Firebase\JWT\JWT;

class likesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Users');
        $this->loadModel('Comments');
        $this->loadModel('Follows');
        $this->loadComponent('RequestHandler');
    }

    public function add() {
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), 
                                   $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            $exists = $this->Likes->find('all', [
                'conditions' => [
                    ['Likes.post_id' => $postData['post_id']],
                    ['Likes.user_id' => $postData['user_id']]
                ]
            ])->first();
            
            if(!$exists) {
                $like = $this->Likes->newEntity();
                $like->post_id = $postData['post_id'];
                $like->user_id = $postData['user_id'];
                $result = $this->Likes->save($like);
            }
            $datum = ['success' => (isset($result)) ? true : false];
            return $this->jsonResponse($datum);
        }
    }

    public function delete() {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $postData = get_object_vars($request->data);
        $exists = $this->Likes->find('all', [
                                        'conditions' => [
                                            ['Likes.post_id' => $postData['post_id']],
                                            ['Likes.user_id' => $postData['user_id']]
                                        ]
                                    ])->first();
                  
        if($exists) {
            $status = $exists->deleted ? 0 : 1;
            $exists->deleted = $status;
            $result = $this->Likes->save($exists);
        }
        $datum = ['success' => (isset($result)) ? true : false];
        return $this->jsonResponse($datum);
    }
}