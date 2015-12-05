<?php
/**
 * User: profet
 * Date: 12/5/15
 * Time: 1:59 AM
 */

namespace App\Listener;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ViewListener {

    protected $templating;

    function __construct($templating)
    {
        $this->templating = $templating;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $data = $event->getControllerResult();

        $controller = $request->attributes->get('_controller');
        $controllerName = get_class($controller[0]);
        $controllerName = substr($controllerName,0,-10);
        $actionName = $controller[1];
        $actionName = substr($actionName,0,-6);

        $controllerName = end(explode('\\',$controllerName));
        $viewPath = $controllerName . '/' . $actionName . '.twig';

        $content = $this->templating->render($viewPath, $data);

        $response = new Response($content);

        $event->setResponse($response);
    }

}