<?php

namespace App\Controller;

use App\Entity\Figure;
use App\Entity\Picture;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="snowtricks_home")
     */
    public function index(): Response
    {
        $repository = $this->getDoctrine()->getRepository(Figure::class);

        $figures = $repository->findAll();
        $firstPictures = [];
        foreach ($figures as $figure) {
            $figure = $repository->find($figure->getId());

            if ($figure == null) {
                throw $this->createNotFoundException('La figure n\'a pas été trouvée');
            }

            array_push($firstPictures, $figure->getPictures()->first());

        }

        return $this->render('home/index.html.twig', [
            'figures' => $figures,
            'firstPictures' => $firstPictures
        ]);
    }

}
