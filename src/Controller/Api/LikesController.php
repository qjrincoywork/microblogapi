<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Firebase\JWT\JWT;

/**
 * LikesController of API
 */
class LikesController extends AppController
{
    /**
     * CommentsController initialize
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
     * Use method add to save like or to like a post.
     *
     * @return array
     */
    public function add() {
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $postData = get_object_vars($request->data);
            $exists = $this->Likes->find('all', [
                'conditions' => [
                    ['Likes.post_id' => $postData['post_id']],
                    ['Likes.user_id' => $postData['user_id']],
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

    /**
     * Use method delete to delete like in a post or to unlike a post.
     *
     * @return array
     */
    public function delete() {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $postData = get_object_vars($request->data);
        $exists = $this->Likes->find('all', [
                                        'conditions' => [
                                            ['Likes.post_id' => $postData['post_id']],
                                            ['Likes.user_id' => $postData['user_id']],
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
