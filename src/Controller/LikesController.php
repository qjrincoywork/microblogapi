<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;

/**
 * Likes Controller
 *
 * @property \App\Model\Table\LikesController $Likes
 */
class LikesController extends AppController
{
    /**
     * LikesController initialize
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
     * Use add method to add like to Posts.
     *
     * @param int ...$postId Posts id.
     * @return array
     */
    public function add($postId)
    {
        $userId = $this->request->getSession()->read('Auth.User.id');
        $postData['post_id'] = $postId;
        $postData['user_id'] = $userId;
        $result = $this->apiGateWay('/api/likes/add.json', $postData);

        if (isset($result->success) && $result->success) {
            $datum['success'] = $result->success;
        } else {
            $datum = get_object_vars($result);
        }

        return $this->jsonResponse($datum);
    }

    /**
     * Use delete method to delete/unlike to Posts.
     *
     * @param int ...$postId Posts id.
     * @return array
     */
    public function delete($postId)
    {
        $userId = $this->request->getSession()->read('Auth.User.id');
        $postData['post_id'] = $postId;
        $postData['user_id'] = $userId;
        $result = $this->apiGateWay('/api/likes/delete.json', $postData);

        if (isset($result->success) && $result->success) {
            $datum['success'] = $result->success;
        } else {
            $datum = get_object_vars($result);
        }

        return $this->jsonResponse($datum);
    }
}
