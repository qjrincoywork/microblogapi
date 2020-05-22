<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Controller\Controller;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * AppController of API
 */
class AppController extends Controller
{
    /**
     * AppController initialize
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    /**
     * Use method formErrors to return form errors.
     *
     * @param array ...$data Data.
     * @return array
     */
    public function formErrors($data)
    {
        $errors = [];
        foreach ($data->getErrors() as $key => $val) {
            $errors[$key] = array_values($val);
        }

        return $errors;
    }

    /**
     * Use method jsonResponse to return Json Decoded data.
     *
     * @param array ...$data Data.
     * @return array
     */
    public function jsonResponse($data)
    {
        $jsonData = json_encode($data);
        $response = $this->response->withType('json')->withStringBody($jsonData);

        return $response;
    }

    /**
     * Use method sendEmail to Send Email to newly registered user.
     *
     * @param string ...$username Username.
     * @param string ...$name Full name.
     * @param string ...$to Email to send to.
     * @param string ...$token Token to activate account.
     * @return array
     */
    public function sendEmail($username, $name, $to, $token)
    {
        try {
            $activationUrl = (isset($_SERVER['HTTPS']) === 'on' ? "https" : "http") .
                                                                  "://$_SERVER[HTTP_HOST]" .
                                                                  "/users/activation/" . $token;
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

    /**
     * Use method getSharedPost to return Shared Post by id.
     *
     * @param int ...$postId Posts id.
     * @return array
     */
    public function getSharedPost($postId)
    {
        $post = TableRegistry::get('Posts');
        $data = $post->find('all', [
                                'contain' => ['Users'],
                                'conditions' => ['Posts.deleted' => 0,'Posts.id' => $postId],
                            ])->first();

        return $data;
    }

    /**
     * Use method reactionCount to return Reaction Count on a Post.
     *
     * @param int ...$postId Posts id.
     * @param string ...$reaction Model.
     * @return array
     */
    public function reactionCount($postId, $reaction)
    {
        $model = TableRegistry::get($reaction);
        $count = $model->find('all', [
                              'conditions' => [$reaction . ".post_id" => $postId,
                                               $reaction . ".deleted" => 0],
        ])->count();

        return $count;
    }

    /**
     * Use method likedBefore to check if the post has been liked before.
     *
     * @param int ...$postId Posts id.
     * @param int ...$userId User id.
     * @return array
     */
    public function likedBefore($postId, $userId)
    {
        $post = TableRegistry::get('Likes');
        $data = $post->find('all', [
                            'conditions' => ["Likes.user_id" => $userId, "Likes.post_id" => $postId],
                            ])->first();
        $hasReacted = $data ? true : false;

        return $hasReacted;
    }

    /**
     * Use method postReaction to check if the user have been liked, shared, commented the post.
     *
     * @param int ...$postId Posts id.
     * @param int ...$userId User id.
     * @param string ...$reaction Model.
     * @return array
     */
    public function postReaction($postId, $userId, $reaction)
    {
        $post = TableRegistry::get($reaction);
        $data = $post->find('all', [
                                'conditions' => [$reaction . '.user_id' => $userId,
                                                 $reaction . ".post_id" => $postId,
                                                 $reaction . ".deleted" => 0],
                            ])->first();

        $hasReacted = $data ? true : false;

        return $hasReacted;
    }

    /**
     * Use method isFollowing to check if the user is following the pased following id.
     *
     * @param int ...$myId User id.
     * @param int ...$followingId User id - other users id.
     * @return array
     */
    public function isFollowing($myId, $followingId)
    {
        $follow = TableRegistry::get('Follows');
        $data = $follow->find('all', [
                                'conditions' => [
                                    ['Follows.user_id' => $myId],
                                    ['Follows.following_id' => $followingId],
                                    ['Follows.deleted' => 0],
                                ],
                            ])->first();
        $isFollowing = !empty($data) ? true : false;

        return $isFollowing;
    }

    /**
     * Use method hadFollowed to check if the user is had been followed already.
     *
     * @param int ...$myId User id.
     * @param int ...$followingId User id - other users id.
     * @return array
     */
    public function hadFollowed($myId, $followingId)
    {
        $follow = TableRegistry::get('Follows');

        $data = $follow->find('all', [
            'conditions' => [
                ['Follows.user_id' => $myId],
                ['Follows.following_id' => $followingId],
            ],
        ])->first();
        $hadFollowed = !empty($data) ? true : false;

        return $hadFollowed;
    }
}
