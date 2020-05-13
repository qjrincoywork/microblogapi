<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Firebase\JWT\JWT;
use Cake\Utility\Security;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler', [
            'enableBeforeRedirect' => false,
        ]);
        $this->loadComponent('Flash');
        // $this->loadComponent('Session');
        $this->loadComponent('Paginator');
        $this->loadComponent('Auth', [
            'loginAction' => ['controller' => 'index', 'action' => 'index'],
            'logoutRedirect' => ['controller' => 'index', 'action' => 'register'],
            'authenticate' => [
                'Form' => [
                    'fields' => [
                        'username' => 'username',
                        'password' => 'password'
                    ]
                ]
            ],
            'storage' => 'Session',
            'unauthorizedRedirect' => false
        ]);
        // $this->loadComponent('Csrf');
        /*
         * Enable the following component for recommended CakePHP security settings.
         * see https://book.cakephp.org/3.0/en/controllers/components/security.html
         */
        // $this->loadComponent('Security');
    }

    public function beforeFilter(Event $event) {
        $this->Auth->allow(['index', 'register', 'activation', 'logout', 'testEmail']);
    }

    public function beforeRender(Event $event) {
        $auth = $this->request->getSession()->read('Auth.User');
        $myId = $this->request->getSession()->read('Auth.User.id');
        $systemLogo = "/img/microbloglogo.png";
        
        $this->set(compact('auth', 'myId', 'systemLogo'));
    }

    public function formErrors($data) {
        $errors = [];
        foreach($data->getErrors() as $key => $val) {
            $errors[$key] = array_values($val);
        }
        return $errors;
    }

    public function jsonResponse($data){
        $jsonData = json_encode($data);
        $response = $this->response->withType('json')->withStringBody($jsonData);
        return $response;
    }
    
    public function apiPostGateWay($url, $data)
    {
        $output = array();
        try {
            $ch = curl_init();
            $host = (isset($_SERVER['HTTPS']) === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            $fullUrl = $host.$url;
            $key = JWT::encode([
                        'exp' => time() + 604800,
                        'data' => $data,
                    ], Security::salt());

            $payload = array(
                "iss" => $host,
                "iat" => time(),
                'exp' => time() + 604800,
                "data" => $data
            );
            
            $token = JWT::encode($payload, $key);
            $jsonData = json_encode(['token' => $token, 'api_key' => $key]);
            
            $header = array('Content-Type:application/json');
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            $data = json_decode(strstr($result, '{'));
            curl_close($ch);
            
            return $data;
        } catch (Exception $e) {
            dd('error');
        }
    }
    
    public function apiGateWay($url, $data)
    {
        $output = array();
        try {
            $ch = curl_init();
            $host = (isset($_SERVER['HTTPS']) === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            $fullUrl = $host.$url;
            $key = JWT::encode([
                        'exp' => time() + 604800,
                        'data' => $data,
                    ], Security::salt());
                    
            $payload = array(
                "iss" => $host,
                "iat" => time(),
                'exp' => time() + 604800,
                "data" => $data
            );
            
            $token = JWT::encode($payload, $key);
            $jsonData = json_encode(['token' => $token, 'api_key' => $key]);
            
            $header = array('Content-Type:application/json');
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            // pr($result); die('app');
            $data = json_decode(strstr($result, '{'));
            curl_close($ch);
            
            return $data;
        } catch (Exception $e) {
            dd('error');
        }
    }
    
    public function apiGetGateWay($url, $data)
    {
        $output = array();
        try {
            $ch = curl_init();
            $host = (isset($_SERVER['HTTPS']) === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            $fullUrl = $host.$url;
            $key = JWT::encode([
                        'exp' => time() + 604800,
                        'data' => $data,
                    ], Security::salt());
                    
            $payload = array(
                "iss" => $host,
                "iat" => time(),
                'exp' => time() + 604800,
                "data" => $data
            );
            
            $token = JWT::encode($payload, $key);
            $jsonData = json_encode(['token' => $token, 'api_key' => $key]);
            
            $header = array('Content-Type:application/json');
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            // pr($result); die('app');
            $data = json_decode(strstr($result, '[{'));
            curl_close($ch);
            
            return $data;
        } catch (Exception $e) {
            dd('error');
        }
    }
}
