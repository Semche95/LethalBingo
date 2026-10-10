<?php

namespace App\Controller;

use App\Entity\Game;
use App\Service\CsrfGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game')]
class GameController extends AbstractController
{
    #[Route('/{slug}', name: 'app_game', requirements: ['slug' => '[a-zA-Z0-9]{12}'])]
    public function game(#[MapEntity(expr: 'repository.findOneForPlay(slug)')] Game $game): Response
    {
        return $this->render('game/game.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route(
        '/save-notes/{id}',
        name: 'app_game_save_notes',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted('ROLE_REFEREE')]
    public function saveNotes(Request $request, EntityManagerInterface $em, Game $game, CsrfGuard $csrfGuard): Response
    {
        $csrfGuard->assertValid('save-notes-' . $game->getId(), $request->headers->get('X-CSRF-Token'));

        $data = json_decode($request->getContent(), true);
        $notes = $data['notes'] ?? '';

        $game->setNotes($notes);
        $em->persist($game);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
