<?php

namespace App\Controller;

use App\Entity\Figure;
use App\Entity\User;
use App\Form\FigureType;
use App\Services\FlashService;
use App\Services\FormService;
use App\Services\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FigureController extends AbstractController
{
    private $entityManager;
    private $flash;
    private $checkForm;

    public function __construct(EntityManagerInterface $entityManager, FlashService $flash, FormService $checkForm)
    {
        $this->entityManager = $entityManager;
        $this->flash = $flash;
        $this->checkForm = $checkForm;
    }

    /**
     * @Route("/figure/{idFigure}", name="snowtricks_figure")
     * @return Response
     */
    public function index(int $idFigure): Response
    {
        $repository = $this->getDoctrine()->getRepository(Figure::class);

        $figure = $repository->find($idFigure);

        if ($figure == null) {
            throw $this->createNotFoundException('La figure n\'a pas été trouvée');
        }

        $picture = $figure->getPictures()->first();

        return $this->render('figure/index.html.twig', [
            'figure' => $figure,
            'picture' => $picture
        ]);
    }

    /**
     * @Route("/create-figure", name="snowtricks_createfigure")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @return Response
     */
    public function createFigure(Request $request): Response
    {
        $figure = new Figure();

        $form = $this->createForm(FigureType::class, $figure);
        $form->handleRequest($request);
        $repository = $this->getDoctrine()->getRepository(User::class);
        if ($form->isSubmitted() && $form->isValid() && $this->checkForm->checkFigure($figure, $form)) {
            $user = $repository->findOneBy(['username' => $this->getUser()->getUsername()]);
            $figure->setUser($user);

            $this->entityManager->persist($figure);
            $this->entityManager->flush();

            $this->flash->setFlashMessages(http_response_code(), 'Création réussite !');

            return $this->redirectToRoute('snowtricks_figure', ['id' => $figure->getId()]);

        }

        return $this->render('figure/formFigure.html.twig', [
            'formFigure' => $form->createView(),
            'editMode' => null
        ]);
    }

    /**
     * @Route("/edit-figure/{idFigure}", name="snowtricks_editfigure")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @return Response
     */
    public function modifyFigure(int $idFigure, Request $request, MediaService $editMedia): Response
    {
        if (null === $figure = $this->entityManager->getRepository(Figure::class)->find($idFigure)) {
            throw $this->createNotFoundException('No figure found for id ' . $idFigure);
        }

        $originalPictures = $editMedia->originalMedia($figure->getPictures());
        $originalVideos = $editMedia->originalMedia($figure->getVideos());


        $form = $this->createForm(FigureType::class, $figure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->checkForm->checkFigure($figure, $form)) {

            $editMedia->editMedia($figure->getPictures(), $originalPictures);
            $editMedia->editMedia($figure->getVideos(), $originalVideos);
            $figure->setModifiedAt();
            $this->entityManager->flush();

            $this->flash->setFlashMessages(http_response_code(), 'Modification réussite !');

            return $this->redirectToRoute('snowtricks_figure', ['idFigure' => $idFigure]);
        }

        return $this->render('figure/formFigure.html.twig', [
            'formFigure' => $form->createView(),
            'editMode' => $figure->getId()
        ]);
    }

    /**
     * @Route("/delete-figure/{idFigure}", name="snowtricks_deletefigure")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @return RedirectResponse
     */
    public function deleteFigure(int $idFigure): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $repository = $this->getDoctrine()->getRepository(Figure::class);

        $figure = $repository->find($idFigure);
        foreach ($figure->getVideos() as $video) {
            $this->entityManager->remove($video);
        }
        foreach ($figure->getPictures() as $picture) {
            $this->entityManager->remove($picture);
        }
        $this->entityManager->remove($figure);
        $this->entityManager->flush();

        $this->flash->setFlashMessages(http_response_code(), 'Suppréssion réussite !');

        return $this->redirectToRoute('snowtricks_home');
    }
}
