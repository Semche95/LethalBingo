<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Entity\Game;
use App\Form\GameFormType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/referee/game')]
class GameController extends AbstractController
{
    #[Route('', name: 'app_referee_game')]
    public function index(GameRepository $repository): Response
    {
        $games = $repository->findAll();
        return $this->render('referee/game/index.html.twig', [
            'games' => $games,
        ]);
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

            return $this->redirectToRoute('app_referee_bingo_edit', [
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
}
