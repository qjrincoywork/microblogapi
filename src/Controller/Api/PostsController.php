<?php
namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Mailer\Email;
use Cake\Mailer\TransportFactory;
use Cake\ORM\TableRegistry;
use Firebase\JWT\JWT;

class PostsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Users');
        $this->loadModel('Comments');
        $this->loadModel('Follows');
        $this->loadComponent('RequestHandler');
    }

    public function add()
    {
        $post = $this->Posts->newEntity();
        $datum['success'] = false;
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), 
                                   $this->request->getData('api_key'), ['HS256']);
                                   
            $postData = get_object_vars($request->data);
            if($postData['image'] != 'undefined') {
                $postData['image'] = get_object_vars($postData['image']);
            }
            $post = $this->Posts->patchEntity($post, $postData);

            if($post->image == 'undefined') {
                $post->image = null;
            } else {
                $uploadFolder = "img/".$post->user_id;
                
                if(!file_exists($uploadFolder)) {
                    mkdir($uploadFolder);
                }
                
                $path = $uploadFolder."/".$postData['image']['name'];
                
                if(copy($postData['image']['tmp_name'], $path)) {
                    $post->image = $path;
                }
            }
            
            if(!$post->getErrors()) {
                if ($this->Posts->save($post)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($post);
                $datum['errors'] = $errors;
            }
            
            return $this->jsonResponse($datum);
        }
    }

    public function view()
    {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $data = $this->Posts->find('all', ['contain' => ['Users'],
                                           'conditions' => ['Posts.id' => $request->data,'Posts.deleted' => 0],
                                   ])->first();
                                   
        return $this->jsonResponse($data);
    }

    public function postComments()
    {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $this->paginate = [
            'limit' => 3,
            'contain' => ['Users'],
            'conditions' => ['Comments.post_id' => $request->data, 'Comments.deleted' => 0],
            'order' => [
                'Comments.created'
            ]
        ];
        $datum = $this->paginate('Comments');
        return $this->jsonResponse($datum);
    }

    public function commentCount()
    {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $id = $request->data;
        $data = $this->Comments->find('all')
                               ->select()
                               ->where(['Comments.deleted' => 0, 'Comments.post_id' => $id])
                               ->count();
        return $this->jsonResponse(['rows' => $data]);
    }
}