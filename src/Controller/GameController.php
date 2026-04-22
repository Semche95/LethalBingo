<?php

namespace App\Controller;

use App\Entity\Game;
use App\Service\Bingo;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/game')]
class GameController extends AbstractController
{
    public function __construct(private readonly HubInterface $hub)
    {
    }

    #[Route('/{slug}', name: 'app_game', requirements: ['slug' => '[a-zA-Z0-9]{12}'])]
    public function game(Bingo $bingo, Game $game): Response
    {
        $bingoDto = $bingo->entityToDto($game->getRound());
        return $this->render('game/game.html.twig', [
            'game' => $game,
            'squares' => $bingoDto->squares,
        ]);
    }

    #[Route(
        '/start/{id}',
        name: 'app_game_start',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted('ROLE_REFEREE')]
    public function start(Request $request, EntityManagerInterface $em, Game $game): Response
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('start-game', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $game->setStartedAt(new DateTimeImmutable());
        $em->persist($game);
        $em->flush();

        $this->refreshBingoCardTitle($game);
        $this->addFlash('success', 'Partie démarrée');

        return $this->redirectToRoute('app_game', ['slug' => $game->getSlug()]);
    }

    #[Route(
        '/stop/{id}',
        name: 'app_game_stop',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted('ROLE_REFEREE')]
    public function stop(Request $request, EntityManagerInterface $em, Game $game): Response
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('stop-game', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $game->setFinishedAt(new DateTimeImmutable());
        $em->persist($game);
        $em->flush();

        $this->refreshBingoCardTitle($game);
        $this->addFlash('success', 'Partie terminée');

        return $this->redirectToRoute('app_game', ['slug' => $game->getSlug()]);
    }

    #[Route(
        '/reset/{id}',
        name: 'app_game_reset',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted('ROLE_REFEREE')]
    public function reset(Request $request, EntityManagerInterface $em, Game $game): Response
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('reset-game', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $em->wrapInTransaction(function ($em) use ($game) {
            $game
                ->setStartedAt(null)
                ->setFinishedAt(null);
            $game->getBingoSquareRevealed()->clear();
            foreach ($game->getBingoSquareValidated() as $square) {
                $em->remove($square);
            }
            $em->persist($game);
        });

        $this->refreshBingoCardTitle($game);
        $this->addFlash('success', 'Partie réinitialisée');

        return $this->redirectToRoute('app_game', ['slug' => $game->getSlug()]);
    }

    private function refreshBingoCardTitle(Game $game): void
    {
        $this->hub->publish(new Update(
            'bingo-card-title',
            $this->renderView('game/card-title.stream.html.twig', ['game' => $game]),
        ));
    }

    #[Route(
        '/save-notes/{id}',
        name: 'app_game_save_notes',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
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
