<?php

namespace App\Controller\Base;

use Symfony\Component\HttpFoundation\Request;
use Twig_Environment;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGenerator;

abstract class WebController
{
    /** @var Request */
    protected $request;

    /** @var Twig_Environment */
    protected $templating;

    /** @var EntityManager */
    protected $entityManager;

    /** @var UrlGenerator */
    protected $urlGenerator;

    function __construct(
        Request $request,
        Twig_Environment $templating,
        EntityManager $entityManager,
        UrlGenerator $urlGenerator
    ) {
        $this->request = $request;
        $this->templating = $templating;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }
}