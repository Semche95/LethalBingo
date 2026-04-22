<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/referee')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_referee_dashboard')]
    public function index(): Response
    {
        return $this->render('referee/dashboard/index.html.twig');
    }
}
