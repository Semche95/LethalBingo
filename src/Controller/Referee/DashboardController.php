<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Repository\GameRepository;
use App\Repository\GameRoundRepository;
use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/referee')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_referee_dashboard')]
    public function index(
        GameRepository $gameRepository,
        GameRoundRepository $gameRoundRepository,
        TeamRepository $teamRepository,
    ): Response {
        return $this->render('referee/dashboard/index.html.twig', [
            'recent_games' => $gameRepository->findRecent(),
            'recent_game_rounds' => $gameRoundRepository->findRecent(),
            'recent_teams' => $teamRepository->findRecent(),
        ]);
    }
}
