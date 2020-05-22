<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;

/**
 * Posts Controller
 *
 * @property \App\Model\Table\PostsTable $Posts
 */
class PostsController extends AppController
{
    /**
     * Posts Controller initialize
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
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
     * Use view method to display the post of the user and the comments
     *
     * @param int ...$id Posts id.
     * @return void
     */
    public function view($id)
    {
        $data = $this->apiGateWay('/api/posts/view.json', $id);
        $column = $this->apiGateWay('/api/posts/commentCount.json', $id);
        $pages = ceil($column->rows / 3);
        $page = $this->request->getQuery('page');

        if ($page <= $pages) {
            $comments = $this->apiGetGateWay("/api/posts/postComments.json?page=" . $page, $id);
        } else {
            $comments = $this->apiGetGateWay('/api/posts/postComments.json', $id);
        }

        $this->set(compact('data', 'comments', 'pages'));
        $this->set('title', 'User Post');
    }

    /**
     * Use add method to create post
     *
     * @return array
     */
    public function add()
    {
        $post = $this->Posts->newEntity();
        $datum['success'] = false;
        if ($this->request->is('post')) {
            $id = $this->request->getSession()->read('Auth.User.id');
            $postData = $this->request->getData();
            $postData['user_id'] = $id;
            $result = $this->apiGateWay('/api/posts/add.json', $postData);

            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
    }

    /**
     * Use share method to share others post
     *
     * @return array
     * @param int ...$id Shared Post id.
     */
    public function share($id)
    {
        $post = $this->Posts->newEntity();
        $datum['success'] = false;
        if ($this->request->is('post')) {
            $userId = $this->request->getSession()->read('Auth.User.id');
            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/posts/share.json', $postData);

            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $data = $this->apiGateWay('/api/posts/userPost.json', $id);
        $this->set(compact('data', 'post'));
    }

    /**
     * Use edit method to edit post
     *
     * @return array
     * @param int ...$id Posts id.
     */
    public function edit($id)
    {
        $post = $this->apiGateWay('/api/posts/userPost.json', $id);
        if ($this->request->is(['post'])) {
            $userId = $this->request->getSession()->read('Auth.User.id');
            if ($post->user_id != $userId) {
                $datum['error'] = 'Unable to process action.';

                return $this->jsonResponse($datum);
            }
            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/posts/edit.json', $postData);
            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $this->set(compact('post'));
    }

    /**
     * Use delete method to delete post
     *
     * @return array
     * @param int ...$id Posts id.
     */
    public function delete($id)
    {
        $post = $this->apiGateWay('/api/posts/userPost.json', $id);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $userId = $this->request->getSession()->read('Auth.User.id');
            if ($post->user_id != $userId) {
                $datum['error'] = 'Unable to process action.';

                return $this->jsonResponse($datum);
            }

            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/posts/delete.json', $postData);
            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $this->set(compact('post'));
    }
}
