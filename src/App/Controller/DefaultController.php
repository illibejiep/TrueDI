<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class DefaultController {

    protected $request;

    protected $templating;

    function __construct($request, $templating)
    {
        var_dump(get_class());
        $this->request = $request;
        $this->templating = $templating;
    }


    function defaultAction()
    {
        $content = $this->templating->render(
            'Default/default.twig',
            array(
                'name' => 'asdf',
            )
        );

        return new Response($content);
    }

    function asdfAction()
    {
        return new Response('asdf');
    }
}