<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Utility\Security;
use Cake\Mailer\Email;
use Cake\Mailer\TransportFactory;
use Cake\ORM\TableRegistry;
/**
 * Index Controller
 *
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IndexController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Posts');
        $this->loadModel('Follows');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->setLayout('main');
        
        if($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout(false);
        }
    }

    public function index()
    {
        $this->set('title', 'User Login');
        $this->viewBuilder()->setLayout('default');
        if($this->request->is('post')) {
            $result = $this->apiGateWay('/api/users/login.json', $this->request->getData());
            if(isset($result->success)) {
                $user = get_object_vars($result->data);
                $this->Auth->setUser($user);
                $datum['success'] = true;
            } else {
                $datum = get_object_vars($result);
            }
            return $this->jsonResponse($datum);
        }
    }

    public function register()
    {
        if($this->request->getSession()->read('Auth.User.id')) {
            return $this->redirect(['action' => 'home']);
        }
        
        $this->viewBuilder()->setLayout('default');
        if($this->request->is('post')) {
            $result = $this->apiGateWay('/api/users/register.json', $this->request->getData());
            if(isset($result->success) && $result->success) {
                $datum['success'] = $result->success;
            } else {
                $datum = get_object_vars($result);
            }
            return $this->jsonResponse($datum);
        }
    }
}
