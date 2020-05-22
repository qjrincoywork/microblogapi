<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;

/**
 * Index Controller
 */
class IndexController extends AppController
{
    /**
     * IndexController initialize
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
        $this->loadModel('Follows');
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
     * Use index method to login user to app.
     *
     * @return array
     */
    public function index()
    {
        if ($this->request->getSession()->read('Auth.User.id')) {
            return $this->redirect(['action' => 'home']);
        }
        $this->set('title', 'User Login');
        $this->viewBuilder()->setLayout('default');
        if ($this->request->is('post')) {
            $result = $this->apiGateWay('/api/users/login.json', $this->request->getData());
            if (isset($result->success)) {
                $user = get_object_vars($result->data);
                $this->Auth->setUser($user);
                $datum['success'] = true;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
    }

    /**
     * Use register method to register user to app.
     *
     * @return array
     */
    public function register()
    {
        $this->set('title', 'User Registration');
        if ($this->request->getSession()->read('Auth.User.id')) {
            return $this->redirect(['action' => 'home']);
        }

        $this->viewBuilder()->setLayout('default');
        if ($this->request->is('post')) {
            $result = $this->apiGateWay('/api/users/register.json', $this->request->getData());
            if (isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }

            return $this->jsonResponse($datum);
        }
    }
}
