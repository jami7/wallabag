<?php

namespace Wallabag\CoreBundle\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wallabag\CoreBundle\Entity\ArticleSearch;
use Wallabag\CoreBundle\Form\Type\EntryFilterType;
use Wallabag\CoreBundle\Form\Type\EditEntryType;
use Wallabag\CoreBundle\Form\Type\SearchType;

use Elastica\Index;
use Elastica\Query;
use Elastica\Query\Term;
use Pagerfanta\Adapter\ElasticaAdapter;
use Pagerfanta\Adapter\ArrayAdapter;

class SearchController extends Controller
{
    /**
     * @param Request $request
     *
     * @Route("/search/{page}", name="search", defaults={"page" = "1"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchFormAction(Request $request, $page = 1) {
        //$article_type = $this->get('fos_elastica.index.wallabag.entry');

        $articleSearch = new ArticleSearch();

        $form = $this->createForm(SearchType::class,$articleSearch);
        $form->handleRequest($request);

        if ($form->isValid()) {

            //$page = 1;
            $repositoryManager = $this->get('fos_elastica.manager.orm');
            $repository = $repositoryManager->getRepository('WallabagCoreBundle:Entry');
            $search = $articleSearch->getSearchTerm();
            $logger = $this->get('logger');
            $logger->error($search);

            $articles = $repository->find($search);

            $adapter = new ArrayAdapter($articles);
            $entries = new Pagerfanta($adapter);

            $entries->setMaxPerPage($this->getUser()->getConfig()->getItemsPerPage());
            $entries->setCurrentPage($page);
            $form = $this->createForm(EntryFilterType::class);

            return $this->render(
                'WallabagCoreBundle:Entry:entries.html.twig',
                array(
                    'form' => $form->createView(),
                    'entries' => $entries,
                    'currentPage' => $page,
                )
            );
        }
        return $this->render('WallabagCoreBundle:Search:new_form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @param Request $request
     *
     * @Route("/search-form", name="search-form")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request)
    {
        return $this->render('WallabagCoreBundle:Search:new.html.twig');
    }
}
