<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Dto\GameDto;
use App\Entity\Game;
use App\Form\GameFormType;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/add', name: 'app_referee_game_add')]
    public function add(Request $request, GameService $gameService): Response
    {
        $form = $this->createForm(GameFormType::class, new GameDto());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $game = $gameService->create($form->getData());

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
    public function edit(Request $request, GameService $gameService, Game $game): Response
    {
        $form = $this->createForm(GameFormType::class, $gameService->entityToDto($game), ['disabled' => $game->isReadOnly()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !$game->isReadOnly()) {
            $gameService->update($form->getData(), $game);

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
