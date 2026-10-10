<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Dto\BingoDto;
use App\Entity\GameRound;
use App\Form\BingoFormType;
use App\Repository\GameRoundRepository;
use App\Service\BingoService;
use App\Service\CsrfGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/add', name: 'app_referee_bingo_add')]
    public function add(Request $request, BingoService $bingo): Response
    {
        return $this->handleForm($request, $bingo, new BingoDto(), null);
    }

    #[Route('/add-round/{season}', name: 'app_referee_bingo_add_round', requirements: ['season' => '\d+'], methods: [Request::METHOD_POST])]
    public function addRound(Request $request, int $season, BingoService $bingo, GameRoundRepository $gameRoundRepository, CsrfGuard $csrfGuard): RedirectResponse
    {
        $csrfGuard->assertValid('add-round-' . $season, $request->getPayload()->getString('token'));

        $maxRound = $gameRoundRepository->findMaxNumberForSeason($season);
        $gameRound = $bingo->createEmptyRound($season, $maxRound + 1);

        $this->addFlash('success', sprintf('Round %d de la saison %d créé', $gameRound->getNumber(), $season));

        return $this->redirectToRoute('app_referee_bingo');
    }

    #[Route('/add-season', name: 'app_referee_bingo_add_season', methods: [Request::METHOD_POST])]
    public function addSeason(Request $request, BingoService $bingo, GameRoundRepository $gameRoundRepository, CsrfGuard $csrfGuard): RedirectResponse
    {
        $csrfGuard->assertValid('add-season', $request->getPayload()->getString('token'));

        $maxSeason = $gameRoundRepository->findMaxSeason();
        $newSeason = $maxSeason + 1;
        $bingo->createEmptyRound($newSeason, 1);

        $this->addFlash('success', sprintf('Saison %d créée (round 1)', $newSeason));

        return $this->redirectToRoute('app_referee_bingo');
    }

    #[Route('/fill-gaps/{season}', name: 'app_referee_bingo_fill_gaps', requirements: ['season' => '\d+'], methods: [Request::METHOD_POST])]
    public function fillGaps(Request $request, int $season, BingoService $bingo, CsrfGuard $csrfGuard): RedirectResponse
    {
        $csrfGuard->assertValid('fill-gaps-' . $season, $request->getPayload()->getString('token'));

        $bingo->fillSeasonGaps($season);

        $this->addFlash('success', sprintf('Les rounds manquants de la saison %d ont été ajoutés', $season));

        return $this->redirectToRoute('app_referee_bingo');
    }

    #[Route('/edit/{id}', name: 'app_referee_bingo_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        BingoService $bingo,
        #[MapEntity(expr: 'repository.findOneWithSquares(id)')] GameRound $gameRound,
    ): Response {
        return $this->handleForm($request, $bingo, $bingo->entityToDto($gameRound), $gameRound);
    }

    // Shared by add ($gameRound null) and edit.
    private function handleForm(Request $request, BingoService $bingo, BingoDto $bingoDto, ?GameRound $gameRound): Response
    {
        $form = $this->createForm(BingoFormType::class, $bingoDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($gameRound === null) {
                $gameRound = $bingo->create($form->getData());
            } else {
                $bingo->update($form->getData(), $gameRound);
            }

            $this->addFlash('success', 'Bingo enregistré');

            return $this->redirectToRoute('app_referee_bingo_edit', [
                'id' => $gameRound->getId(),
            ]);
        }

        return $this->render('referee/bingo/form.html.twig', [
            'data' => $bingoDto,
            'gameRound' => $gameRound,
            'form' => $form,
            'mode' => $gameRound === null ? 'add' : 'edit',
            'duplicateCoords' => $bingoDto->duplicateCoords,
        ]);
    }

    #[Route(
        '/delete/{id}',
        name: 'app_referee_bingo_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST]
    )]
    public function delete(Request $request, EntityManagerInterface $em, GameRound $gameRound, CsrfGuard $csrfGuard): RedirectResponse
    {
        $csrfGuard->assertValid('delete-bingo', $request->getPayload()->getString('token'));

        if (!$gameRound->getGames()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer un bingo qui a des parties');

            return $this->redirectToRoute('app_referee_bingo_edit', ['id' => $gameRound->getId()]);
        }

        $em->remove($gameRound);
        $em->flush();

        $this->addFlash('success', 'Bingo supprimé');

        return $this->redirectToRoute('app_referee_bingo');
    }
}
