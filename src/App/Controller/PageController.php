<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\Page;

class PageController {

    /** @var Request  */
    protected $request;

    protected $templating;

    /** @var  EntityRepository */
    protected $pageRepository;

    function __construct(Request $request, $templating, $pageRepository)
    {
        $this->request = $request;
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