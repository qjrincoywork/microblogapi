<?php
namespace App\Controller\Api;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;
use Firebase\JWT\JWT;

class AppController extends Controller
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    public function formErrors($data) {
        $errors = [];
        foreach($data->getErrors() as $key => $val) {
            $errors[$key] = array_values($val);
        }
        return $errors;
    }
    
    public function jsonResponse($data){
        $jsonData = json_encode($data);
        $response = $this->response->withType('json')->withStringBody($jsonData);
        return $response;
    }
    
    public function sendEmail($username, $name, $to, $token) {
        try {
            $activationUrl = (isset($_SERVER['HTTPS']) === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . "/users/activation/" . $token;
            $subject = "Microblog Account Activation";
            
            $email = new Email('gmail');
            $email->setFrom([$to => 'Microblog 3'])
                  ->setEmailFormat('html')
                  ->setTo($to)
                  ->setSubject($subject)
                  ->setViewVars(['name' => $name, 
                                 'email' => $to,
                                 'username' => $username, 
                                 'url' => $activationUrl])
                  ->viewBuilder()
                  ->setLayout('activation')
                  ->setTemplate('default');
            return $email->send();
        } catch (\Throwable $th) {
            echo $th;
        }
    }
    
    public function getSharedPost($postId) {
        $post = TableRegistry::get('Posts');
        $data = $post->find('all', [
                                'contain' => ['Users'],
                                'conditions' => ['Posts.deleted' => 0,'Posts.id' => $postId]
                            ])->first();
        return $data;
    }
    
    public function reactionCount($postId, $reaction) {
        $model = TableRegistry::get($reaction);
        $count = $model->find('all',[
            'conditions' => [$reaction.".post_id" => $postId,
                             $reaction.".deleted" => 0]
        ])->count();
        
        return $count;
    }
    
    public function likedBefore($postId, $userId) {
        $post = TableRegistry::get('Likes');
        $data = $post->find('all',[
                                'conditions' => ["Likes.user_id" => $userId,
                                                "Likes.post_id" => $postId]
                            ])->first();
        
        $hasReacted = ($data) ? true : false;
        return $hasReacted;
    }

    public function postReaction($postId, $userId, $reaction) {
        $post = TableRegistry::get($reaction);
        $data = $post->find('all',[
                                'conditions' => [$reaction.'.user_id' => $userId, 
                                                $reaction.".post_id" => $postId,
                                                $reaction.".deleted" => 0]
                            ])->first();
        
        $hasReacted = ($data) ? true : false;
        return $hasReacted;
    }
    
    public function postCount()
    {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $id = $request->data->user_id;
        $following = $this->Follows->find()
                                   ->select('Follows.following_id')
                                   ->where(['Follows.user_id' => $id, 'Follows.deleted' => 0])
                                   ->toArray();
        $ids = [];
        foreach($following as $key => $val) {
            $ids[] = $val['following_id'];
        }
        $ids[] = $id;
        $data = $this->Posts->find('all')
                            ->select()
                            ->where(['Posts.deleted' => 0, 'Posts.user_id IN' => $ids])
                            ->count();
        return $this->jsonResponse(['rows' => $data]);
    }
}
