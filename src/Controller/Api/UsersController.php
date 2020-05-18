<?php
namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Mailer\Email;
use Cake\Mailer\TransportFactory;
use Cake\ORM\TableRegistry;
use Firebase\JWT\JWT;
/**
 * Users Controller
 *
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
        $this->loadModel('Follows');
        $this->loadComponent('RequestHandler');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->setLayout('main');
        
        if($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout(false);
        }
    }
    
    public function getPosts($conditions) {
        $this->paginate = [
            'Posts' => [
                'contain' => ['Users'],
                'conditions' => [
                    $conditions,
                ],
                'limit' => 4,
                'order' => [
                    'created' => 'desc',
                ],
            ]
        ];
        return $this->paginate($this->Posts);
    }
    
    public function index()
    {
        $data = $this->getPosts(['Posts.deleted' => 0, 'Posts.user_id' => 1]);
        $this->set(['data' => $data, '_serialize' => ['data']]);
    }
    
    public function home()
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
        $data = $this->getPosts(['Posts.deleted' => 0, 'Posts.user_id IN' => $ids]);
        return $this->jsonResponse($data);
    }
    
    public function profile()
    {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $profile = $this->Users->find('all', [
                                        'conditions' => ['Users.id' => $request->data, 'Users.is_online !=' => 2]
                                     ])->first();
        return $this->jsonResponse($profile);
    }
    
    public function profilePosts()
    {
        $request = JWT::decode($this->request->getData('token'), 
                               $this->request->getData('api_key'), ['HS256']);
        $condition = get_object_vars($request->data->condition);
        $id = $request->data->user_id;
        $posts = $this->getPosts($condition);
        $data = [];
        foreach ($posts as $post) {
            $likedBefore = $this->likedBefore($post->id, $id);
            $sharedPost = $this->getSharedPost($post->post_id);
            $isLiked = $this->postReaction($post->id, $id, 'Likes');
            $isCommented = $this->postReaction($post->id, $id, 'Comments');
            $isShared = $this->postReaction($post->id, $id, 'Posts');
            
            $likeCount = $this->reactionCount($post->id, 'Likes');
            $commentCount = $this->reactionCount($post->id, 'Comments');
            $shareCount = $this->reactionCount($post->id, 'Posts');
            
            $post->shared_post = $sharedPost;
            $post->liked_before = $likedBefore;
            $post->is_liked = $isLiked;
            $post->is_commented = $isCommented;
            $post->is_shared = $isShared;

            $post->like_count = $likeCount;
            $post->comment_count = $commentCount;
            $post->share_count = $shareCount;
            $data[] = $post;
        }
        return $this->jsonResponse($data);
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

    public function userCount()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $postData = get_object_vars($request->data);
        $postData['conditions'] = get_object_vars($request->data->conditions);
        
        $ids = $this->Follows->find('list', ['valueField' => $postData['column']])
                             ->where($postData['conditions'])->toArray();
        if($ids) {
            $data = $this->Users->find('all')
                                ->select()
                                ->where(['Users.is_online !=' => 2, 'Users.deleted' => 0,'Users.id IN' => $ids])
                                ->count();
        }
        
        return $this->jsonResponse(['rows' => $data]);
    }

    public function login()
    {
        if($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $user = $this->Users->find()->where(['username' => $request->data->username])->first();
            $valid = password_verify($request->data->password, $user->password);
            
            if($valid) {
                if($user->is_online == 2) {
                    $datum['error'] = 'Please activate your account first.';
                } else {
                    $datum['success'] = true;
                    $datum['data'] = $user;
                }
            } else {
                $datum['error'] = 'Invalid username or password.';
            }
            
            return $this->jsonResponse($datum);
        }
    }
    
    public function sendEmail($userName, $fullName, $to, $token) {
        try {
            $activationUrl = (isset($_SERVER['HTTPS']) === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . "/users/activation/" . $token;
            $subject = "Microblog Account Activation";

            $email = new Email('gmail');
            $email->setFrom([$to => 'Microblog 3'])
                    ->setEmailFormat('html')
                    ->setTo($to)
                    ->setSubject($subject)
                    ->setViewVars(['name' => $fullName, 
                                   'email' => $to,
                                   'username' => $userName, 
                                   'url' => $activationUrl])
                    ->viewBuilder()
                    ->setLayout('activation')
                    ->setTemplate('default');
            return $email->send();
        } catch (\Throwable $th) {
            echo $th;
        }
    }

    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }

    public function register()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $mytoken = Security::hash(Security::randomBytes(32));
            $postData = get_object_vars($request->data);
            $postData['token'] = $mytoken;
            $user = $this->Users->patchEntity($user, $postData, ['validate' => 'Register']);
            
            if(!$user->getErrors()) {
                if ($this->Users->save($user)) {
                    $fullName = $user->last_name.', '.$user->first_name.' '.$user->middle_name;
                    $userName = $user->username;
                    $to = $user->email;
                    if($this->sendEmail($userName, $fullName, $to, $mytoken)) {
                        $datum['success'] = true;
                    }
                }
            } else {
                $errors = $this->formErrors($user);
                $datum['errors'] = $errors;
            }
            return $this->jsonResponse($datum);
        }
    }

    public function activation($token) {
        if(!$token) {
            throw new NotFoundException();
            $this->Flash->error(__('Invalid token'));
        }
        $user = $this->Users->find('all', ['conditions' => ['Users.token' => $token]])->first();
        
        if(!$user) {
            throw new NotFoundException();
            $this->Flash->error(__('Invalid token!'));
        }
        
        if(isset($user['is_online']) && $user['is_online'] == 2) {
            $user->set(['is_online' => 0]);
            $this->Users->save($user);
            $this->Flash->success(__('Account successfully verified!, You can now login'));
            $this->redirect(['controller' => 'users', 'action' => 'login']);
        } else {
            $this->Flash->error(__('Account was already verified!'));
            $this->redirect(['controller' => 'users', 'action' => 'login']);
        }
    }

    public function search($user) {
        $conditions = [];
        if($user){
            $cond = [];
            $cond['first_name LIKE'] = "%" . trim($user) . "%";
            $cond['last_name LIKE'] = "%" . trim($user) . "%";
            $cond['email LIKE'] = "%" . trim($user) . "%";
            $cond['middle_name LIKE'] = "%" . trim($user) . "%";
            $cond['suffix LIKE'] = "%" . trim($user) . "%";
            $cond["CONCAT(first_name,' ',last_name) LIKE"] = "%" . trim($user) . "%";
            $conditions['OR'] = $cond;
        }
        $this->paginate = [
            'conditions' => [
                ['is_online !=' => 2],
                ['deleted' => 0],
                [$conditions],
            ],
            'limit' => 5,
            'order' => [
                'created' => 'desc',
            ],
        ];
        $data = $this->paginate($this->Users);
        
        $this->set(compact('data'));
    }
    
    public function edit() {
        if($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            $user = $this->Users->get($postData['id']);
            $user = $this->Users->patchEntity($user, $postData, ['validate' => 'Update']);
            
            if(!$user->getErrors()) {
                if ($this->Users->save($user)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($user);
                $datum['errors'] = $errors;
            }
            
            return $this->jsonResponse($datum);
        }
    }

    public function following() {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $postData = get_object_vars($request->data);
        $postData['conditions'] = get_object_vars($request->data->conditions);
        
        $ids = $this->Follows->find('list', ['valueField' => $postData['column']])
                             ->where($postData['conditions'])->toArray();
                             
        if($ids) {
            $this->paginate = [
                'Users' => [
                    'conditions' => [
                        ['Users.is_online !=' => 2],
                        ['Users.deleted' => 0],
                        ['Users.id IN' => $ids],
                    ],
                    'limit' => 4,
                    'order' => [
                        'Users.created' => 'desc',
                    ],
                ]
            ];
            $data = $this->paginate();
        }
        
        return $this->jsonResponse($data);
    }

    public function editPicture() {
        if($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            
            if($postData['image'] != 'undefined') {
                $postData['image'] = get_object_vars($postData['image']);
            }
            $user = $this->Users->get($postData['id']);
            
            if($postData['image'] == 'undefined') {
                $postData['image'] = null;
                $user = $this->Users->patchEntity($user, $postData);
            } else {
                $user = $this->Users->patchEntity($user, $postData);
                $uploadFolder = "img/".$postData['id'];
                
                if(!file_exists($uploadFolder)) {
                    mkdir($uploadFolder);
                }

                $path = $uploadFolder."/".$postData['image']['name'];
                
                if(copy($postData['image']['tmp_name'], $path)) {
                    $user->image = $path;
                }
            }
            
            if(!$user->getErrors()) {
                if ($this->Users->save($user)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($user);
                $datum['errors'] = $errors;
            }
            
            return $this->jsonResponse($datum);
        }
    }

    public function changePassword() {
        $id = $this->request->getSession()->read('Auth.User.id');
        $user = $this->Users->get($id);
        
        if($this->request->is(['put', 'patch'])) {
            $datum['success'] = false;
            $postData = $this->request->getData();
            $user = $this->Users->patchEntity($user, $postData, ['validate' => 'Passwords']);
            
            if(!$user->getErrors()) {
                if ($this->Users->save($user)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($user);
                $datum['errors'] = $errors;
            }
            
            return $this->jsonResponse($datum);
        }
        unset($user['password']);
        $this->set(compact('user'));
    }
    
    public function follow($followingId) {
        $id = $this->request->getSession()->read('Auth.User.id');
        $user = $this->Users->get($followingId);
        if($user) {
            $exists = $this->Follows->find('all', [
                                                'conditions' => [
                                                    ['Follows.following_id' => $followingId], 
                                                    ['Follows.user_id' => $id]
                                                ]
                                           ])->first();
                                           
            if(!$exists) {
                $follow = $this->Follows->newEntity();
                $follow->user_id = $id;
                $follow->following_id = $followingId;
                $result = $this->Follows->save($follow);
            }
        }
        $datum = ['success' => (isset($result)) ? true : false];
        return $this->jsonResponse($datum);
    }

    public function unfollow($followingId) {
        $id = $this->request->getSession()->read('Auth.User.id');
        $user = $this->Users->get($followingId);
        if($user) {
            $exists = $this->Follows->find('all', [
                                                'conditions' => [
                                                    ['Follows.following_id' => $followingId], 
                                                    ['Follows.user_id' => $id]
                                                ]
                                           ])->first();
                                           
            if($exists) {
                $status = $exists->deleted ? 0 : 1;
                $exists->deleted = $status;
                $result = $this->Follows->save($exists);
            }
        }
        $datum = ['success' => (isset($result)) ? true : false];
        return $this->jsonResponse($datum);
    }
}
