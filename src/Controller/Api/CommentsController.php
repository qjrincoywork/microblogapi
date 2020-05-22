<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Firebase\JWT\JWT;

/**
 * CommentsController of API
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
        $this->loadComponent('RequestHandler');
    }

    /**
     * Use method add to save comment
     *
     * @return array
     */
    public function add()
    {
        $comment = $this->Comments->newEntity();
        $datum['success'] = false;
        if ($this->request->is('post')) {
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $commentData = get_object_vars($request->data);
            $comment = $this->Comments->patchEntity($comment, $commentData);
            if (!$comment->getErrors()) {
                if ($this->Comments->save($comment)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($comment);
                $datum['errors'] = $errors;
            }

            return $this->jsonResponse($datum);
        }
    }

    /**
     * Use method edit to edit comment.
     *
     * @return array
     */
    public function edit()
    {
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $commentData = get_object_vars($request->data);
            $comment = $this->Comments->get($commentData['id']);
            $comment = $this->Comments->patchEntity($comment, $commentData);

            if (!$comment->getErrors()) {
                if ($this->Comments->save($comment)) {
                    $datum['success'] = true;
                }
            } else {
                $errors = $this->formErrors($comment);
                $datum['errors'] = $errors;
            }

            return $this->jsonResponse($datum);
        }
    }

    /**
     * Use method delete to delete comment.
     *
     * @return array
     */
    public function delete()
    {
        if ($this->request->is(['post'])) {
            $datum['success'] = false;
            $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
            $commentData = get_object_vars($request->data);
            $commentData['deleted'] = 1;
            $comment = $this->Comments->get($commentData['id']);
            $comment = $this->Comments->patchEntity($comment, $commentData, ['validate' => 'Delete']);

            if ($comment->getErrors()) {
                $errors = $this->formErrors($comment);
                $datum['errors'] = $errors;
            } else {
                if ($this->Comments->save($comment)) {
                    $datum['success'] = true;
                }
            }

            return $this->jsonResponse($datum);
        }
    }

    /**
     * Use method userComment to get Users Comment by comment id.
     *
     * @return array
     */
    public function userComment()
    {
        $request = JWT::decode($this->request->getData('token'), $this->request->getData('api_key'), ['HS256']);
        $id = $request->data;
        $data = $this->Comments->get($id);

        return $this->jsonResponse($data);
    }
}
