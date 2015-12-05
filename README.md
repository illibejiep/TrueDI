# True DI application with Symfony2 components

Symfony2 framework implement DI-container pattern. The 'Kernel' class initizlizes DI-container
and injects it in different components. So DI-container is used as Service Locator in this components.
Symfony2 event has the 'ContainerAware' class for them. Service Locator is an anti-pattern by some opinions.
I am not again Service Locator pattern. It is more simple than DI. But Service Locator and DI-container patterns in
single project is definitely an anti pattern. So lets try to build an Symfony2 application
without using a Service Locator pattern. The main rule is: only DI-container can know about DI-container.

## Container

Lets install DI-container component.

```
  mkdir trueIoc
  cd trueIoc
  composer init
  composer require symfony/dependency-injection
  composer require symfony/config
  composer require symfony/yaml
  mkdir config
  mkdir www
```

Then create front controller.

```
// in www/index.php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require_once('../vendor/autoload.php');

$container = new ContainerBuilder();
$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../config'));
$loader->load('services.yml');
```

## HttpKernel

HttpKernel (not 'Kernel') is base Symfony2 component.
Here is common workflow of this component.
HttpKernel uses HttpFoundation component for Request and Response objects and
EventDispatcher component for event system. They all can be initialized by using DI-container configuration.
Here is it:

```
# in config/events.yml

services:
  dispatcher:
    class: Symfony\Component\EventDispatcher\EventDispatcher

# in config/kernel.yml

services:
  request:
    class: Symfony\Component\HttpFoundation\Request
    factory: [ Symfony\Component\HttpFoundation\Request, createFromGlobals ]
  request_stack:
    class: Symfony\Component\HttpFoundation\RequestStack
  resolver:
    class: Symfony\Component\HttpKernel\Controller\ControllerResolver
  http_kernel:
    class: Symfony\Component\HttpKernel\HttpKernel
    arguments: ["@dispatcher", "@resolver", "@request_stack"]

#in config/services.yml

imports:
  - { resource: 'events.yml' }
  - { resource: 'kernel.yml' }
```

HttpKernel accepts Request object and returns Response object. Response can be obtained in the front controller

```
// in www/index.php

$HTTPKernel = $container->get('http_kernel');
$request = $container->get('request');
$response = $HTTPKernel->handle($request);
$response->send();
```

or can be defined in the config

```
# in config/kernel.yml

  response:
    class: Symfony\Component\HttpFoundation\Response
    factory: [ "@http_kernel", handle]
    arguments: ["@request"]
```

and just get it in the front controller

```
// in www/index.php

$response = $container->get('response');
$response->send();
```

ControllerResolver class tries to get '_controller' property form custom parameters of Request object.
HttpKernel component expects that you will use events to set this parameter.
We can create a routing listener service for this purpose. Or better use the Symfony Routing component.

## Routing


```
composer require symfony/routing
```

Initial configuration looks pretty complex.

```
# in config/routing.yml
  router.file_locator:
    class: Symfony\Component\Config\FileLocator
    arguments: ["../config"]
  router.yml_loader:
    class: Symfony\Component\Routing\Loader\YamlFileLoader
    arguments: ["@router.file_locator"]
  router:
    class: Symfony\Component\Routing\Router
    arguments:
      - "@router.yml_loader"
      - "routes.yml"
      - []
      - "@router.request_context"
  router.listener:
    class: Symfony\Component\HttpKernel\EventListener\RouterListener
    arguments:
      matcher: "@router"
      request_stack: "@request_stack"
      context: "@router.request_context"

# in config/services.yml

imports:
  - { resource: 'routing.yml' }
  - { resource: 'events.yml' }
  - { resource: 'kernel.yml' }
```

And add 'router.listener' to the event dispatcher.

```
# in config/events.yml

  dispatcher:
      class: Symfony\Component\EventDispatcher\EventDispatcher
      calls:
        - [ addSubscriber, ["@router.listener"]]
```

Now we can create a routing config.

```
# in config/routes.yml

home:
  path: /
  defaults:
    _controller: "DefaultController:defaultAction"

some_page:
  path: /page
  defaults:
    _controller: "PageController:defaultAction"
```

And now if autoloader can find classes DefaultController and PageController it will work.
But this classes are useless because we cant inject any service in them. The ControllerResolver just create this
classes without any parameters and return array with class and action method name.
But if ControllerResolver get an array instead string it return it as is. So if we can define parameter '_controller'
as a service it will work. File routing.yml is a part of symfony/routing component and can't know about services.
So we have to define routes as a services manually. We don't need anymore services 'router','router.file_locator' and
'router.yml_loader'.
Here is config:

```
# in config/controllers.yml
service:
  controller.default:
    class: App\Controller\DefaultController
    arguments: [ "@request" ]
  controller.page:
    class: App\Controller\PageController
    arguments: [ "@request" ]

# in config/routing.yml
  route.home:
    class: Symfony\Component\Routing\Route
    arguments:
      path: /
      defaults:
        _controller: ["@controller.default", 'defaultAction']

  route.asdf:
    class: Symfony\Component\Routing\Route
    arguments:
      path: /asdf
      defaults:
        _controller: ["@controller.page", 'defaultAction']

  route.collection:
    class: Symfony\Component\Routing\RouteCollection
    calls:
      - [ add, ["home", "@route.home"] ]
      - [ add, ["some_page", "@route.asdf"] ]

  router.matcher:
      class: Symfony\Component\Routing\Matcher\UrlMatcher
      arguments: [ "@route.collection", "@router.request_context" ]

  router.listener:
      class: Symfony\Component\HttpKernel\EventListener\RouterListener
      arguments:
        matcher: "@router"
        request_stack: "@request_stack"
        context: "@router.request_context"
```

But now here is another problem. Container initializes each controller with depends.
So it will initialize all existed services which definitely is not good. Fortunately, the container
has a lazy loading functional. But it has to be installed.

```
composer require symfony/proxy-manager-bridge

```

```
//in www/index.php

use \Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;

// ...

$container = new ContainerBuilder();
$container->setProxyInstantiator(new RuntimeInstantiator());

// ...

```

Now we can define lazy services.

```
# in config/controllers.yml

services:
  controller.default:
    lazy:true
    class: App\Controller\DefaultController
    arguments: [ "@request" ]
  controller.page:
    lazy:true
    class: App\Controller\PageController
    arguments: [ "@request" ]
```


## View

We are free to use any template engine. Lets install and configure the Twig.


```
  composer require symfony/twig
```

Container config:

```
# in config/view.yml
services:
  templating.twig_loader:
    class: Twig_Loader_Filesystem
    arguments: [ "../src/App/View" ]
  templating.twig:
    class: Twig_Environment
    arguments: [ "@templating.twig_loader" ]

# in config/controllers.yml

services:
  controller.default:
    class: App\Controller\DefaultController
    arguments: [ "@request", "@templating.twig" ]
# ...
```



```
// in src/App/Controller/DefaultController.php

class DefaultController {

    protected $request;

    protected $templating;

    function __construct($request, $templating)
    {
        $this->request = $request;
        $this->templating = $templating;
    }

    function defaultAction()
        {
            return new Response(
                $this->templating->render(
                    'Default/default.twig',
                    array(
                        'name' => 'asdf',
                    )
                )
            );
        }
// ...
```

It doesn't look good. Lets make it through 'kernel.view' event and add some sugar.

```
// in src/App/Listener/ViewListener.php

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
```

events config

```
# in config/events.yml

services:
  dispatcher:
    class: Symfony\Component\EventDispatcher\EventDispatcher
    calls:
      - [ addSubscriber, ["@router.listener"]]
      - [ addListener , ["kernel.view", ["@templating.listener", "onKernelView" ]] ]
```

and controller

```
// in src/App/Controller/PageController.php
// ...

    function defaultAction($name)
    {
        return array(
            'name' => $name,
        );
    }

// ...
```

