<?php
namespace App\Controller\Api;

use Cake\Controller\Controller;
use Cake\Event\Event;

class AppController extends Controller
{
    public function initialize()
    {
        parent::initialize();
    }
    
    public function jsonResponse($data){
        $jsonData = json_encode($data);
        $response = $this->response->withType('json')->withStringBody($jsonData);
        return $response;
    }
}
