<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Firebase\JWT\JWT;

/**
 * PostsController of API
 */
class PostsController extends AppController
{
    /**
     * PostsController initialize
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Users');
        $this->loadModel('Comments');
        $this->loadModel('Follows');
        $this->loadComponent('RequestHandler');
    }

    /**
     * Use method add to save post or add a post content.
     *
     * @return array
     */
    public function add()
    {
        $post = $this->Posts->newEntity();
        $datum['success'] = false;
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            if ($postData['image'] != 'undefined') {
                $postData['image'] = get_object_vars($postData['image']);
            }
            $post = $this->Posts->patchEntity($post, $postData);

            if ($post->image == 'undefined') {
                $post->image = null;
            } else {
                $uploadFolder = "img/" . $post->user_id;
                if (!file_exists($uploadFolder)) {
                    mkdir($uploadFolder);
                }

                $path = $uploadFolder . "/" . $postData['image']['name'];
                if (copy($postData['image']['tmp_name'], $path)) {
                    $post->image = $path;
                }
            }

            if (!$post->getErrors()) {
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

    /**
     * Use method edit to edit post or edit a post content.
     *
     * @return array
     */
    public function edit()
    {
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            if ($postData['image'] != 'undefined') {
                $postData['image'] = get_object_vars($postData['image']);
            }
            $post = $this->Posts->get($postData['id']);
            if ($postData['image'] == 'undefined') {
                unset($postData['image']);
                $post = $this->Posts->patchEntity($post, $postData);
            } else {
                $post = $this->Posts->patchEntity($post, $postData);
                $uploadFolder = "img/" . $postData['user_id'];

                if (!file_exists($uploadFolder)) {
                    mkdir($uploadFolder);
                }

                $path = $uploadFolder . "/" . $postData['image']['name'];
                if (copy($postData['image']['tmp_name'], $path)) {
                    $post->image = $path;
                }
            }

            if ($post->getErrors()) {
                $errors = $this->formErrors($post);
                $datum['errors'] = $errors;
            } else {
                if ($this->Posts->save($post)) {
                    $datum['success'] = true;
                }
            }

            return $this->jsonResponse($datum);
        }
    }

    /**
     * Use method share to save share post or repost a post content.
     *
     * @return array
     */
    public function share()
    {
        $post = $this->Posts->newEntity();
        $datum['success'] = false;
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            $post = $this->Posts->patchEntity($post, $postData);

            if (!$post->getErrors()) {
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

    /**
     * Use method view to get user post by post id.
     *
     * @return array
     */
    public function view()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $data = $this->Posts->find('all', ['contain' => ['Users'],
                                           'conditions' => ['Posts.id' => $request->data,'Posts.deleted' => 0],
                                   ])->first();

        return $this->jsonResponse($data);
    }

    /**
     * Use method postComments to get user post comments by post id.
     *
     * @return array
     */
    public function postComments()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $this->paginate = [
            'limit' => 3,
            'contain' => ['Users'],
            'conditions' => ['Comments.post_id' => $request->data, 'Comments.deleted' => 0],
            'order' => [
                'Comments.created',
            ],
        ];
        $datum = $this->paginate('Comments');

        return $this->jsonResponse($datum);
    }

    /**
     * Use method commentCount to get the count of comments in a post by post id.
     *
     * @return array
     */
    public function commentCount()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $id = $request->data;
        $data = $this->Comments->find('all')
                               ->select()
                               ->where(['Comments.deleted' => 0, 'Comments.post_id' => $id])
                               ->count();

        return $this->jsonResponse(['rows' => $data]);
    }

    /**
     * Use method userPost to get the post of a user by post id.
     *
     * @return array
     */
    public function userPost()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $id = $request->data;
        $data = $this->Posts->get($id, [
            'contain' => ['Users'],
        ]);

        return $this->jsonResponse($data);
    }

    /**
     * Use method delete to soft delete the post of a user by post id.
     *
     * @return array
     */
    public function delete()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $postData = get_object_vars($request->data);
            $postDetails = $this->Posts->get($postData['id']);
            $post = $this->Posts->patchEntity($postDetails, $postData);

            if ($post->getErrors()) {
                $errors = $this->formErrors($post);
                $datum['errors'] = $errors;
            } else {
                if ($this->Posts->save($post)) {
                    $datum['success'] = true;
                }
            }

            return $this->jsonResponse($datum);
        }
    }
}
