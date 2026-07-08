<?php

namespace App\Controller;

use App\Entity\Game;
use App\Service\BingoService;
use App\Service\GameTimerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game')]
class GameController extends AbstractController
{
    #[Route('/{slug}', name: 'app_game', requirements: ['slug' => '[a-zA-Z0-9]{12}'])]
    public function game(GameTimerService $gameTimerService, BingoService $bingo, Game $game): Response
    {
        $bingoDto = $bingo->entityToDto($game->getRound());
        return $this->render('game/game.html.twig', [
            'game' => $game,
            'timerDisplay' => $gameTimerService->formatGameDuration($game),
            'squares' => $bingoDto->squares,
        ]);
    }

    #[Route(
        '/save-notes/{id}',
        name: 'app_game_save_notes',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted('ROLE_REFEREE')]
    public function saveNotes(Request $request, EntityManagerInterface $em, Game $game): Response
    {
        $data = json_decode($request->getContent(), true);
        $notes = $data['notes'] ?? '';

        $game->setNotes($notes);
        $em->persist($game);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
