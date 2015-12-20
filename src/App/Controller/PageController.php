<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\Page;
use Twig_Environment;

class PageController {

    /** @var  Twig_Environment */
    protected $templating;

    /** @var  EntityRepository */
    protected $pageRepository;

    function __construct($templating, $pageRepository)
    {
        $this->templating = $templating;
        $this->pageRepository = $pageRepository;
    }

    function defaultAction($id)
    {
        /** @var Page $page */
        $page = $this->pageRepository->find($id);
        if (!$page)
            throw new NotFoundHttpException();

        $content = $this->templating->render(
            'Page\default.twig',
            array(
                'title' => $page->getTitle(),
                'content' => $page->getContent(),
            )
        );

        return new Response($content);
    }
}