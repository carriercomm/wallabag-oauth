<?php

namespace Wallabag\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wallabag\CoreBundle\Entity\Entry;
use Wallabag\CoreBundle\Service\Extractor;
use Wallabag\CoreBundle\Helper\Url;

class EntryController extends Controller
{
    /**
     * @param Request $request
     *
     * @Route("/new", name="new_entry")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addEntryAction(Request $request)
    {
        $entry = new Entry($this->getUser());

        $form = $this->createFormBuilder($entry)
            ->add('url', 'url')
            ->add('save', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $content = Extractor::extract($entry->getUrl());

            $entry->setTitle($content->getTitle());
            $entry->setContent($content->getBody());

            $em = $this->getDoctrine()->getManager();
            $em->persist($entry);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice',
                'Entry saved'
            );

            return $this->redirect($this->generateUrl('homepage'));
        }

        return $this->render('WallabagCoreBundle:Entry:new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Shows unread entries for current user
     *
     * @Route("/unread", name="unread")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showUnreadAction(Request $request)
    {
        // TODO change pagination
        /*$entries = $this->getDoctrine()
            ->getRepository('WallabagCoreBundle:Entry')
            ->findUnreadByUser($this->getUser()->getId(), 0);*/

        /*$credentialsClient = $this->get('api.service.client.credentials_client');
        $accessToken = $credentialsClient->getAccessToken();*/
        /*var_dump(sprintf('Obtained Access Token: <info>%s</info>', $accessToken));

        $url = 'http://oauth-server.local/api/articles';
        var_dump(sprintf('Requesting: <info>%s</info>', $url));
        $response = $credentialsClient->fetch($url);
        var_dump(sprintf('Response: <info>%s</info>', var_export($response, true)));*/

        /*$client = $this->get('api.service.client.rest');
        $result = $client->call('/api/entries.json', 'GET');
        var_dump($result);*/
        $api = $this->get('api.service.request');
        $api->setHeaders(array(
                CURLOPT_COOKIESESSION=>$_COOKIE,
            ));
        $token = $api->clientAuthentification();
//echo "token\n"; var_dump($token);
        $api->setAccessToken($token);
        $entries = $api->get('/entries.json', array());

//var_dump($_COOKIE);
//var_dump($request->cookies);

        #$entries = array();
        #$this->get('logger')->info(implode(',',$entries));
        var_dump($entries);

        return $this->render(
            'WallabagCoreBundle:Entry:entries.html.twig',
            array('entries' => $entries)
        );



        //return $this->render('WallabagCoreBundle:Default:index.html.twig', array('entries'=>$entries));
    }

    /**
     * Shows read entries for current user
     *
     * @Route("/archive", name="archive")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showArchiveAction()
    {
        // TODO change pagination
        $entries = $this->getDoctrine()
            ->getRepository('WallabagCoreBundle:Entry')
            ->findArchiveByUser($this->getUser()->getId(), 0);

        return $this->render(
            'WallabagCoreBundle:Entry:entries.html.twig',
            array('entries' => $entries)
        );
    }

    /**
     * Shows starred entries for current user
     *
     * @Route("/starred", name="starred")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showStarredAction()
    {
        // TODO change pagination
        $entries = $this->getDoctrine()
            ->getRepository('WallabagCoreBundle:Entry')
            ->findStarredByUser($this->getUser()->getId(), 0);

        return $this->render(
            'WallabagCoreBundle:Entry:entries.html.twig',
            array('entries' => $entries)
        );
    }

    /**
     * Shows entry content
     *
     * @param Entry $entry
     *
     * @Route("/view/{id}", requirements={"id" = "\d+"}, name="view")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Entry $entry)
    {
        $this->checkUserAction($entry);

        return $this->render(
            'WallabagCoreBundle:Entry:entry.html.twig',
            array('entry' => $entry)
        );
    }

    /**
     * Changes read status for an entry
     *
     * @param Request $request
     * @param Entry   $entry
     *
     * @Route("/archive/{id}", requirements={"id" = "\d+"}, name="archive_entry")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function toggleArchiveAction(Request $request, Entry $entry)
    {
        $this->checkUserAction($entry);

        $entry->toggleArchive();
        $this->getDoctrine()->getManager()->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Entry archived'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Changes favorite status for an entry
     *
     * @param Request $request
     * @param Entry   $entry
     *
     * @Route("/star/{id}", requirements={"id" = "\d+"}, name="star_entry")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function toggleStarAction(Request $request, Entry $entry)
    {
        $this->checkUserAction($entry);

        $entry->toggleStar();
        $this->getDoctrine()->getManager()->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Entry starred'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Deletes entry
     *
     * @param Request $request
     * @param Entry   $entry
     *
     * @Route("/delete/{id}", requirements={"id" = "\d+"}, name="delete_entry")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteEntryAction(Request $request, Entry $entry)
    {
        $this->checkUserAction($entry);

        $entry->setDeleted(1);
        $this->getDoctrine()->getManager()->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Entry deleted'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Check if the logged user can manage the given entry
     *
     * @param Entry $entry
     */
    private function checkUserAction(Entry $entry)
    {
        if ($this->getUser()->getId() != $entry->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can not use this entry.');
        }
    }
}
