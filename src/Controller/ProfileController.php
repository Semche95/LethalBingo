<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('IS_AUTHENTICATED')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_user_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }
}
