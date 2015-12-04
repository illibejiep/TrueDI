# True IoC application with Symfony2 components

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

# in config/services.yml

services:
  request:
    class: Symfony\Component\HttpFoundation\Request
    factory: [ Symfony\Component\HttpFoundation\Request, createFromGlobals ]
  request_stack:
    class: Symfony\Component\HttpFoundation\RequestStack
  dispatcher:
    class: Symfony\Component\EventDispatcher\EventDispatcher
    calls:
      - [ addSubscriber, ["@router.listener"]]
  resolver:
    class: Symfony\Component\HttpKernel\Controller\ControllerResolver
  http_kernel:
    class: Symfony\Component\HttpKernel\HttpKernel
    arguments: ["@dispatcher", "@resolver", "@request_stack"]

```

HttpKernel accepts Request object and returns Response object. So we can get Responce object in the front controller

```
// in www/index.php
$HTTPKernel = $container->get('http_kernel');
$request = $container->get('request');
$response = $HTTPKernel->handle($request);
$response->send();

```

Or we can define response in the config:

```
# in config/services.yml
  response:
    class: Symfony\Component\HttpFoundation\Response
    factory: [ "@http_kernel", handle]
    arguments: ["@request"]


```

and get it in front controller

```

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
# in config/services.yml
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

```

And add 'router.listner' to the event dispatcher.

```

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

  controller.default:
    class: App\Controller\DefaultController
      arguments: [ "@request" ]
  controller.page:
      class: App\Controller\PageController
        arguments: [ "@request" ]

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

