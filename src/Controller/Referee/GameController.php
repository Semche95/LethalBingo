<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Entity\Game;
use App\Form\GameFormType;
use App\Repository\GameRepository;
use App\Repository\GameRoundRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/referee/game')]
#[IsGranted('ROLE_REFEREE')]
class GameController extends AbstractController
{
    #[Route('', name: 'app_referee_game')]
    public function index(): Response
    {
        return $this->render('referee/game/index.html.twig');
    }

    #[Route('/api/filters', name: 'app_referee_game_api_filters', methods: [Request::METHOD_GET])]
    public function apiFilters(GameRoundRepository $rounds, TeamRepository $teams): JsonResponse
    {
        $roundNumbers = $rounds->findDistinctRoundNumbers();
        $seasons = $rounds->findDistinctSeasons();
        $teamRows = $teams->findAllWithMembers();

        return $this->json([
            'rounds' => array_map(fn($n) => (int)$n, $roundNumbers),
            'seasons' => array_map(fn($s) => (int)$s, $seasons),
            'teams' => array_map(static fn(array $t) => ['id' => (int)$t['id'], 'name' => (string)$t['name']], $teamRows),
        ]);
    }

    #[Route('/api/games', name: 'app_referee_game_api_games', methods: [Request::METHOD_GET])]
    public function apiGames(Request $request, GameRepository $games): JsonResponse
    {
        $round = $request->query->has('round') ? $request->query->getInt('round') : null;
        $season = $request->query->has('season') ? $request->query->getInt('season') : null;
        $team = $request->query->has('team') ? $request->query->getInt('team') : null;

        $toBool = static function (?string $val): ?bool {
            return match ($val) {
                'true' => true,
                'false' => false,
                default => null,
            };
        };
        $started = $toBool($request->query->get('started'));
        $ended = $toBool($request->query->get('ended'));

        $list = $games->findByFilters($round, $season, $team, $started, $ended);

        // Normalize datetime fields to ISO 8601 for reliable client parsing
        $normalize = static function (array $row): array {
            $fmt = static function (?string $v): ?string {
                if ($v === null) {
                    return null;
                }
                try {
                    return (new \DateTimeImmutable($v))->format(DATE_ATOM);
                } catch (\Throwable) {
                    return $v;
                }
            };
            $row['startedAt'] = $fmt($row['startedAt'] ?? null);
            $row['finishedAt'] = $fmt($row['finishedAt'] ?? null);
            return $row;
        };

        return $this->json(array_map($normalize, $list));
    }

    #[Route('/add', name: 'app_referee_game_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $game = new Game();
        $form = $this->createForm(GameFormType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($game);
            $em->flush();

            $this->addFlash('success', 'Partie enregistrée');

            return $this->redirectToRoute('app_referee_game_edit', [
                'id' => $game->getId(),
            ]);
        }

        return $this->render('referee/game/form.html.twig', [
            'form' => $form,
            'mode' => 'add',
        ]);
    }

    #[Route('/edit/{id}', name: 'app_referee_game_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, EntityManagerInterface $em, Game $game): Response
    {
        $form = $this->createForm(GameFormType::class, $game, ['disabled' => $game->isReadOnly()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !$game->isReadOnly()) {
            $em->persist($game);
            $em->flush();

            $this->addFlash('success', 'Partie enregistrée');

            return $this->redirectToRoute('app_referee_game_edit', [
                'id' => $game->getId(),
            ]);
        }

        return $this->render('referee/game/form.html.twig', [
            'game' => $game,
            'form' => $form,
            'mode' => 'edit',
        ]);
    }

    #[Route(
        '/delete/{id}',
        name: 'app_referee_game_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST]
    )]
    public function delete(Request $request, EntityManagerInterface $em, Game $game): RedirectResponse
    {
        if ($game->isReadOnly()) {
            throw new BadRequestHttpException();
        }
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('delete-game', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $em->remove($game);
        $em->flush();

        $this->addFlash('success', 'Partie supprimée');

        return $this->redirectToRoute('app_referee_game');
    }

    #[Route(
        '/regenerate-slug/{id}',
        name: 'app_referee_game_regenerate_slug',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST]
    )]
    public function regenerateSlug(Request $request, EntityManagerInterface $em, Game $game): RedirectResponse
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('regenerate-slug', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $game->generateSlug();
        $em->persist($game);
        $em->flush();

        $this->addFlash('success', 'Lien de la partie regénéré');

        return $this->redirectToRoute('app_referee_game_edit', [
            'id' => $game->getId(),
        ]);
    }
}
