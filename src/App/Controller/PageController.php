<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class PageController {

    protected $request;

    function __construct(Request $request)
    {
        $this->request = $request;
    }

    function defaultAction()
    {
        return new Response('page default');
    }

    function asdfAction()
    {
        return new Response('page asdf');
    }
}