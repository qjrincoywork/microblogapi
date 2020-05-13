<?php
namespace App\Controller\Api;

use Cake\Controller\Controller;
use Cake\Event\Event;

class AppController extends Controller
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Paginator');
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
}
