<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class DefaultController {

    protected $request;

    function __construct(Request $request)
    {
        $this->request = $request;
    }

    function defaultAction()
    {
        return new Response('default');
    }

    function asdfAction()
    {
        return new Response('asdf');
    }
}