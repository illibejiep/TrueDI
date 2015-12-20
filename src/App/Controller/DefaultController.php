<?php
namespace App\Controller;

use App\Controller\Base\WebController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends WebController {

    function defaultAction()
    {
        return new Response('Hello cruel world ');
    }
}