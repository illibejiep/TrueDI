<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class PageController {

    protected $request;

    protected $templating;

    function __construct($request, $templating)
    {
        var_dump(get_class());
        $this->request = $request;
        $this->templating = $templating;
    }

    function defaultAction($name)
    {
        return array(
            'name' => $name,
        );
    }

    function asdf()
    {
        return new Response('page asdf');
    }
}