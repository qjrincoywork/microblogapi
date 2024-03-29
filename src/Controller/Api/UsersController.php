<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Utility\Security;
use Firebase\JWT\JWT;

/**
 * Users Controller of API
 */
class UsersController extends AppController
{
    /**
     * UsersController initialize
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
        $this->loadModel('Follows');
        $this->loadComponent('RequestHandler');
    }

    /**
     * Use method getPosts to get posts by condition
     *
     * @param  array ...$conditions Condition for pagination.
     * @return array
     */
    public function getPosts($conditions)
    {
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
            ],
        ];

        return $this->paginate($this->Posts);
    }

    /**
     * Use method home to get posts by condition
     *
     * @return array
     */
    public function home()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $id = $request->data->user_id;
        $following = $this->Follows->find()
                                   ->select('Follows.following_id')
                                   ->where(['Follows.user_id' => $id, 'Follows.deleted' => 0])
                                   ->toArray();
        $ids = [];
        foreach ($following as $key => $val) {
            $ids[] = $val['following_id'];
        }
        $ids[] = $id;
        $posts = $this->getPosts(['Posts.deleted' => 0, 'Posts.user_id IN' => $ids]);
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

    /**
     * Use method profile to get the user profile by user id.
     *
     * @return array
     */
    public function profile()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        if (is_object($request->data)) {
            $profile = $this->Users->find(
                'all',
                ['conditions' => ['Users.id' => $request->data->id, 'Users.is_online !=' => 2]],
            )->first();
            $profile->is_following = $this->isFollowing($request->data->user_id, $profile->id);
            $profile->had_followed = $this->hadFollowed($request->data->user_id, $profile->id);
        } else {
            $profile = $this->Users->find(
                'all',
                ['conditions' => ['Users.id' => $request->data, 'Users.is_online !=' => 2]]
            )->first();
        }

        return $this->jsonResponse($profile);
    }

    /**
     * Use method profilePosts to get the user posts by condition.
     *
     * @return array
     */
    public function profilePosts()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
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

    /**
     * Use method searchCount to get the count of searched users.
     *
     * @return array
     */
    public function searchCount()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $conditions = [];
        if ($request->data->user) {
            $cond = [];
            $cond['first_name LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['last_name LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['email LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['middle_name LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['suffix LIKE'] = "%" . trim($request->data->user) . "%";
            $cond["CONCAT(first_name,' ',last_name) LIKE"] = "%" . trim($request->data->user) . "%";
            $conditions['OR'] = $cond;
        }
        $data = $this->Users->find('all')
            ->select()
            ->where(['Users.is_online !=' => 2, 'Users.deleted' => 0, $conditions])
            ->count();

        return $this->jsonResponse(['rows' => $data]);
    }

    /**
     * Use method userCount to get the count of users.
     *
     * @return array
     */
    public function userCount()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $postData = get_object_vars($request->data);
        $postData['conditions'] = get_object_vars($request->data->conditions);

        $ids = $this->Follows->find('list', ['valueField' => $postData['column']])
            ->where($postData['conditions'])->toArray();
        if ($ids) {
            $data = $this->Users->find('all')
                ->select()
                ->where(['Users.is_online !=' => 2, 'Users.deleted' => 0,'Users.id IN' => $ids])
                ->count();
        }

        return $this->jsonResponse(['rows' => $data]);
    }

    /**
     * Use method postCount to get the count of posts.
     *
     * @return array
     */
    public function postCount()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        if (isset($request->data->id)) {
            $id = $request->data->id;
            $following = $this->Follows->find()
                                       ->select('Follows.following_id')
                                       ->where(['Follows.user_id' => $id, 'Follows.deleted' => 0])
                                       ->toArray();
            $ids = [];
            foreach ($following as $key => $val) {
                $ids[] = $val['following_id'];
            }
            $ids[] = $id;
            $data = $this->Posts->find('all')
                                ->select()
                                ->where(['Posts.deleted' => 0, 'Posts.user_id IN' => $ids])
                                ->count();
        } else {
            $data = $this->Posts->find('all')
                                ->select()
                                ->where(['Posts.deleted' => 0, 'Posts.user_id' => $request->data->user_id])
                                ->count();
        }

        return $this->jsonResponse(['rows' => $data]);
    }

    /**
     * Use method login to validate loging in user.
     *
     * @return array
     */
    public function login()
    {
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $user = $this->Users->find()->where(['username' => $request->data->username])->first();
            $valid = password_verify($request->data->password, $user->password);

            if ($valid) {
                if ($user->is_online == 2) {
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

    /**
     * Use method register to save and validate registration of user.
     *
     * @return array
     */
    public function register()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $mytoken = Security::hash(Security::randomBytes(32));
            $postData = get_object_vars($request->data);
            $postData['token'] = $mytoken;
            $user = $this->Users->patchEntity($user, $postData, ['validate' => 'Register']);

            if (!$user->getErrors()) {
                if ($this->Users->save($user)) {
                    $fullName = $user->last_name . ', ' . $user->first_name . ' ' . $user->middle_name;
                    $userName = $user->username;
                    $to = $user->email;
                    if ($this->sendEmail($userName, $fullName, $to, $mytoken)) {
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

    /**
     * Use method activation to activate user account.
     *
     * @return array
     */
    public function activation()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        if ($this->request->is('post')) {
            $user = $this->Users->find('all', ['conditions' => ['Users.token' => $request->data]])->first();
            if (!$user) {
                $datum['error'] = 'Invalid token!';
            }

            if ($user->is_online == 2) {
                $user->set(['is_online' => 0]);
                if ($this->Users->save($user)) {
                    $datum['success'] = true;
                }
            } else {
                $datum['error'] = 'Account was already verified!';
            }

            return $this->jsonResponse($datum);
        }
    }

    /**
     * Use method search to get the searched users.
     *
     * @return array
     */
    public function search()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $conditions = [];
        if ($request->data->user) {
            $cond = [];
            $cond['first_name LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['last_name LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['email LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['middle_name LIKE'] = "%" . trim($request->data->user) . "%";
            $cond['suffix LIKE'] = "%" . trim($request->data->user) . "%";
            $cond["CONCAT(first_name,' ',last_name) LIKE"] = "%" . trim($request->data->user) . "%";
            $conditions['OR'] = $cond;
        }
        $this->paginate = [
            'conditions' => [
                ['is_online !=' => 2],
                ['deleted' => 0],
                [$conditions],
            ],
            'limit' => 4,
            'order' => [
                'created' => 'desc',
            ],
        ];
        $users = $this->paginate($this->Users);
        $data = [];
        foreach ($users as $user) {
            $user->is_following = $this->isFollowing($request->data->id, $user->id);
            $user->had_followed = $this->hadFollowed($request->data->id, $user->id);
            $data[] = $user;
        }

        return $this->jsonResponse($data);
    }

    /**
     * Use method edit to edit user.
     *
     * @return array
     */
    public function edit()
    {
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            $user = $this->Users->get($postData['id']);
            $user = $this->Users->patchEntity($user, $postData, ['validate' => 'Update']);

            if (!$user->getErrors()) {
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

    /**
     * Use method following to get followers and followed user.
     *
     * @return array
     */
    public function following()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $postData = get_object_vars($request->data);
        $postData['conditions'] = get_object_vars($request->data->conditions);
        $ids = $this->Follows->find('list', ['valueField' => $postData['column']])
            ->where($postData['conditions'])->toArray();

        if ($ids) {
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
                ],
            ];
            $users = $this->paginate();
            $data = [];
            foreach ($users as $user) {
                $user->is_following = $this->isFollowing($postData['id'], $user->id);
                $user->had_followed = $this->hadFollowed($postData['id'], $user->id);
                $data[] = $user;
            }
        }

        return $this->jsonResponse($data);
    }

    /**
     * Use method editPicture to edit user profile.
     *
     * @return array
     */
    public function editPicture()
    {
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            if ($postData['image'] != 'undefined') {
                $postData['image'] = get_object_vars($postData['image']);
            }
            $user = $this->Users->get($postData['id']);

            if ($postData['image'] == 'undefined') {
                $postData['image'] = null;
                $user = $this->Users->patchEntity($user, $postData);
            } else {
                $user = $this->Users->patchEntity($user, $postData);
                $uploadFolder = "img/" . $postData['id'];
                if (!file_exists($uploadFolder)) {
                    mkdir($uploadFolder);
                }

                $path = $uploadFolder . "/" . $postData['image']['name'];
                if (copy($postData['image']['tmp_name'], $path)) {
                    $user->image = $path;
                }
            }

            if (!$user->getErrors()) {
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

    /**
     * Use method changePassword to change user password.
     *
     * @return array
     */
    public function changePassword()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $postData = get_object_vars($request->data);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $user = $this->Users->get($postData['id']);
            $user = $this->Users->patchEntity($user, $postData, ['validate' => 'Passwords']);

            if (!$user->getErrors()) {
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

    /**
     * Use method follow to save following a user.
     *
     * @return array
     */
    public function follow()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $user = $this->Users->get($request->data->following_id);
        if ($user) {
            $exists = $this->Follows->find(
                'all',
                ['conditions' => [
                    ['Follows.following_id' => $request->data->following_id],
                    ['Follows.user_id' => $request->data->user_id]],
                ],
            )->first();

            if (!$exists) {
                $follow = $this->Follows->newEntity();
                $follow->user_id = $request->data->user_id;
                $follow->following_id = $request->data->following_id;
                $result = $this->Follows->save($follow);
            }
        }
        $datum = ['success' => isset($result) ? true : false];

        return $this->jsonResponse($datum);
    }

    /**
     * Use method unfollow to save unfollowing a user.
     *
     * @return array
     */
    public function unfollow()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $user = $this->Users->get($request->data->following_id);

        if ($user) {
            $exists = $this->Follows->find(
                'all',
                ['conditions' => [
                    ['Follows.following_id' => $request->data->following_id],
                    ['Follows.user_id' => $request->data->user_id]],
                ],
            )->first();

            if ($exists) {
                $status = $exists->deleted ? 0 : 1;
                $exists->deleted = $status;
                $result = $this->Follows->save($exists);
            }
        }
        $datum = ['success' => isset($result) ? true : false];

        return $this->jsonResponse($datum);
    }
}
