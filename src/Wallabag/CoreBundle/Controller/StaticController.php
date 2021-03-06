<?php

namespace Wallabag\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StaticController extends Controller
{
    /**
     * @Route("/about", name="about")
     */
    public function aboutAction()
    {
        return $this->render(
            'WallabagCoreBundle:Static:about.html.twig',
            array()
        );
    }

    /**
     * @Route("/", name="homepage")
     */
    public function apiAction()
    {
        return $this->redirect($this->generateUrl('nelmio_api_doc_index'));
    }
}
