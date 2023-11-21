<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChocoblastController extends AbstractController
{
    #[Route('/chocoblast', name: 'app_chocoblast')]
    public function index(): Response
    {
        return $this->render('chocoblast/index.html.twig', [
            'controller_name' => 'ChocoblastController',
        ]);
    }
}
