<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Users Controller
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
    /**
     * Users Controller initialize
     * loads Posts model and RequestHandler Component
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
        $this->loadComponent('RequestHandler');
    }

    /**
     * beforeFilter sets the layout
     *
     * @param array ...$event Event.
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->setLayout('main');
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout(false);
        }
    }

    /**
     * Use method home to display the posts of the user and the posts of followed users
     *
     * @return void
     */
    public function home()
    {
        $this->set('title', 'Home');
        $id = $this->request->getSession()->read('Auth.User.id');
        $postColumn = $this->apiGateWay('/api/users/postCount.json', ['id' => $id]);
        $pages = ceil($postColumn->rows / 4);
        $post = $this->Posts->newEntity();
        $page = $this->request->getQuery('page');

        if ($page <= $pages) {
            $data = $this->apiGetGateWay("/api/users/home.json?page=" . $page, ['user_id' => $id]);
        } else {
            $data = $this->apiGetGateWay('/api/users/home.json', ['user_id' => $id]);
        }
        $this->set(compact('post', 'data', 'pages'));
    }

    /**
     * logs out user
     *
     * @return null
     */
    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }

    /**
     * Use this method to activate user account
     *
     * @return null
     * @param string ...$token is activation string token.
     */
    public function activation($token)
    {
        if (!$token) {
            throw new NotFoundException();
        }
        $result = $this->apiGateWay('/api/users/activation.json', $token);
        if (isset($result->success) && $result->success) {
            $this->Flash->success(__('Account successfully verified!, You can now login'));
            $this->redirect('/');
        } else {
            $this->Flash->error(__($result->error));
            $this->redirect('/');
        }
    }

    /**
     * Use this method to display user profile and user posts
     *
     * @return array
     */
    public function profile()
    {
        $this->set('title', 'User Profile');
        if (!$this->request->getQuery('id')) {
            throw new NotFoundException();
        }
        $id = $this->request->getQuery('id');
        $myId = $this->request->getSession()->read('Auth.User.id');
        $profile = $this->apiGateWay('/api/users/profile.json', ['user_id' => $myId, 'id' => $id]);
        if (!$profile) {
            throw new NotFoundException();
        }
        if ($myId != $id) {
            $condition = ['Posts.user_id' => $id, 'Posts.deleted' => 0];
        } else {
            $condition = ['Posts.user_id' => $id];
        }
        $postColumn = $this->apiGateWay('/api/users/postCount.json', ['user_id' => $id]);
        $pages = ceil($postColumn->rows / 4);
        $page = $this->request->getQuery('page');
        if ($page <= $pages) {
            $data = $this->apiGetGateWay(
                "/api/users/profilePosts.json?page=" . $page,
                ['user_id' => $myId, 'condition' => $condition]
            );
        } else {
            $data = $this->apiGetGateWay(
                '/api/users/profilePosts.json',
                ['user_id' => $myId, 'condition' => $condition]
            );
        }
        $this->set(compact('id', 'data', 'profile', 'pages'));
    }

    /**
     * Use this method displays to search user by: first name, last name, middle name, suffix and email
     *
     * @return array
     */
    public function search()
    {
        $this->set('title', 'Search User');
        $myId = $this->request->getSession()->read('Auth.User.id');
        $searchedUser = $this->request->getQuery('user');
        if (!$searchedUser) {
            throw new NotFoundException();
        }
        $searchColumn = $this->apiGateWay('/api/users/searchCount.json', ['user' => $searchedUser]);
        $pages = 0;
        if ($searchColumn) {
            $pages = ceil($searchColumn->rows / 4);
        }
        $page = $this->request->getQuery('page');
        if ($page <= $pages) {
            $data = $this->apiGetGateWay(
                "/api/users/search.json?page=" . $page,
                ['id' => $myId, 'user' => $searchedUser]
            );
        } else {
            $data = $this->apiGetGateWay("/api/users/search.json", ['id' => $myId, 'user' => $searchedUser]);
        }
        $this->set(compact('data', 'pages', 'searchedUser'));
    }

    /**
     * Use this method to edit user profile
     *
     * @return array
     */
    public function edit()
    {
        $id = $this->request->getSession()->read('Auth.User.id');
        $user = $this->apiGateWay('/api/users/profile.json', $id);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $postData = $this->request->getData();
            $postData['id'] = $id;
            $result = $this->apiGateWay('/api/users/edit.json', $postData);

            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $this->set(compact('user'));
    }

    /**
     * Use this method to display users followers and followed
     *
     * @return array
     */
    public function following()
    {
        $this->set('title', 'User Follows');
        $field = key($this->request->getQuery());
        $myId = $this->request->getSession()->read('Auth.User.id');
        $id = $this->request->getQuery()[$field];
        $data = [];
        $profile = $this->apiGateWay('/api/users/profile.json', ['id' => $id, 'user_id' => $myId]);
        if (!$profile) {
            throw new NotFoundException();
        }
        $conditions = ['Follows.' . $field => $id,'Follows.deleted' => 0];
        if ($field == 'user_id') {
            $column = 'following_id';
            $message = 'No user following';
        } else {
            $column = 'user_id';
            $message = "Don't have any follower";
        }
        $userColumn = $this->apiGateWay(
            '/api/users/userCount.json',
            ['column' => $column, 'conditions' => $conditions]
        );
        $pages = 0;
        if ($userColumn) {
            $pages = ceil($userColumn->rows / 4);
        }
        $page = $this->request->getQuery('page');
        if ($page <= $pages) {
            $data = $this->apiGetGateWay(
                "/api/users/following.json?page=" . $page,
                ['id' => $myId, 'column' => $column, 'conditions' => $conditions]
            );
        } else {
            $data = $this->apiGetGateWay(
                '/api/users/following.json',
                ['id' => $myId, 'column' => $column, 'conditions' => $conditions]
            );
        }
        $this->set(compact('profile', 'message', 'data', 'pages', 'field', 'id'));
    }

    /**
     * Use this method to edit user profile picture
     *
     * @return array
     */
    public function editPicture()
    {
        $id = $this->request->getSession()->read('Auth.User.id');
        $user = $this->apiGateWay('/api/users/profile.json', $id);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $postData = $this->request->getData();
            $postData['id'] = $id;
            $result = $this->apiGateWay('/api/users/editPicture.json', $postData);
            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $this->set(compact('user'));
    }

    /**
     * Use this method to change user password
     *
     * @return array
     */
    public function changePassword()
    {
        $this->set('title', 'User change password');
        $id = $this->request->getSession()->read('Auth.User.id');
        $user = $this->apiGateWay('/api/users/profile.json', $id);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $postData = $this->request->getData();
            $postData['id'] = $id;
            $result = $this->apiGateWay('/api/users/changePassword.json', $postData);
            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }

        $this->set(compact('user'));
    }

    /**
     * Use this method to follow user
     *
     * @return array
     */
    public function follow()
    {
        $followingId = $this->request->getQuery('following_id');
        if (!$followingId) {
            throw new NotFoundException();
        }
        $id = $this->request->getSession()->read('Auth.User.id');
        $datum = $this->apiGateWay('/api/users/follow.json', ['user_id' => $id,'following_id' => $followingId]);

        return $this->jsonResponse($datum);
    }

    /**
     * Use this method to unfollow user
     *
     * @return array
     */
    public function unfollow()
    {
        $followingId = $this->request->getQuery('following_id');
        if (!$followingId) {
            throw new NotFoundException();
        }

        $id = $this->request->getSession()->read('Auth.User.id');
        $datum = $this->apiGateWay('/api/users/unfollow.json', ['user_id' => $id,'following_id' => $followingId]);

        return $this->jsonResponse($datum);
    }
}
