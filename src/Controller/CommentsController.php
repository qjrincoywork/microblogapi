<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;

/**
 * Comments Controller
 *
 * @property \App\Model\Table\CommentsController $Comments
 */
class CommentsController extends AppController
{
    /**
     * CommentsController initialize
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
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
     * Use add method to add Comment to Posts.
     *
     * @param int ...$id Posts id.
     * @return array
     */
    public function add($id)
    {
        $comment = $this->Comments->newEntity();
        if ($this->request->is('post')) {
            $datum['success'] = false;
            $userId = $this->request->getSession()->read('Auth.User.id');
            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/comments/add.json', $postData);

            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $post = $this->apiGateWay('/api/posts/userPost.json', $id);
        $data = get_object_vars($post);
        $this->set(compact('data', 'comment'));
    }

    /**
     * Use edit method to edit Comment to Posts.
     *
     * @param int ...$id Comment id.
     * @return array
     */
    public function edit($id)
    {
        $comment = $this->apiGateWay('/api/comments/userComment.json', $id);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $userId = $this->request->getSession()->read('Auth.User.id');
            if ($comment->user_id != $userId) {
                $datum['error'] = 'Unable to process action.';

                return $this->jsonResponse($datum);
            }

            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/comments/edit.json', $postData);

            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $this->set(compact('comment'));
    }

    /**
     * Use delete method to delete Comment to Posts permanently.
     *
     * @param int ...$id Comment id.
     * @return array
     */
    public function delete($id)
    {
        $comment = $this->apiGateWay('/api/comments/userComment.json', $id);
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $userId = $this->request->getSession()->read('Auth.User.id');
            if ($comment->user_id != $userId) {
                $datum['error'] = 'Unable to process action.';

                return $this->jsonResponse($datum);
            }
            $postData = $this->request->getData();
            $postData['user_id'] = $userId;
            $result = $this->apiGateWay('/api/comments/delete.json', $postData);

            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
        $this->set(compact('comment'));
    }
}
