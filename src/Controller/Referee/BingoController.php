<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Dto\BingoDto;
use App\Entity\GameRound;
use App\Form\BingoFormType;
use App\Repository\GameRoundRepository;
use App\Service\BingoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/referee/bingo')]
#[IsGranted('ROLE_REFEREE')]
class BingoController extends AbstractController
{
    #[Route('', name: 'app_referee_bingo')]
    public function index(): Response
    {
        return $this->render('referee/bingo/index.html.twig');
    }

    #[Route('/api/filters', name: 'app_referee_bingo_api_filters', methods: [Request::METHOD_GET])]
    public function apiFilters(GameRoundRepository $rounds): JsonResponse
    {
        $roundNumbers = $rounds->findDistinctRoundNumbers();
        $seasons = $rounds->findDistinctSeasons();

        return $this->json([
            'rounds' => array_map(fn($n) => (int)$n, $roundNumbers),
            'seasons' => array_map(fn($s) => (int)$s, $seasons),
        ]);
    }

    #[Route('/api/bingos', name: 'app_referee_bingo_api_bingos', methods: [Request::METHOD_GET])]
    public function apiBingos(Request $request, GameRoundRepository $rounds): JsonResponse
    {
        $round = $request->query->has('round') ? $request->query->getInt('round') : null;
        $season = $request->query->has('season') ? $request->query->getInt('season') : null;

        $list = $rounds->findByRoundAndSeason($round, $season);

        return $this->json($list);
    }

    #[Route('/add', name: 'app_referee_bingo_add')]
    public function add(Request $request, BingoService $bingo): Response
    {
        $bingoDto = new BingoDto();
        $form = $this->createForm(BingoFormType::class, $bingoDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gameRound = $bingo->create($form->getData());

            $this->addFlash('success', 'Bingo enregistré');

            return $this->redirectToRoute('app_referee_bingo_edit', [
                'id' => $gameRound->getId(),
            ]);
        }

        return $this->render('referee/bingo/form.html.twig', [
            'data' => $bingoDto,
            'form' => $form,
            'mode' => 'add',
        ]);
    }

    #[Route('/edit/{id}', name: 'app_referee_bingo_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, BingoService $bingo, GameRound $gameRound): Response
    {
        $bingoDto = $bingo->entityToDto($gameRound);
        $form = $this->createForm(BingoFormType::class, $bingoDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bingo->update($form->getData(), $gameRound);

            $this->addFlash('success', 'Bingo enregistré');

            return $this->redirectToRoute('app_referee_bingo_edit', [
                'id' => $gameRound->getId(),
            ]);
        }

        return $this->render('referee/bingo/form.html.twig', [
            'data' => $bingoDto,
            'gameRound' => $gameRound,
            'form' => $form,
            'mode' => 'edit',
        ]);
    }

    #[Route(
        '/delete/{id}',
        name: 'app_referee_bingo_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST]
    )]
    public function delete(Request $request, EntityManagerInterface $em, GameRound $gameRound): RedirectResponse
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('delete-bingo', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $em->remove($gameRound);
        $em->flush();

        $this->addFlash('success', 'Bingo supprimé');

        return $this->redirectToRoute('app_referee_bingo');
    }
}
