<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Dto\BingoDto;
use App\Entity\GameRound;
use App\Form\BingoFormType;
use App\Service\BingoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            'duplicateCoords' => $bingoDto->duplicateCoords,
        ]);
    }

    #[Route('/add-round/{season}', name: 'app_referee_bingo_add_round', requirements: ['season' => '\d+'], methods: [Request::METHOD_POST])]
    public function addRound(Request $request, int $season, BingoService $bingo): RedirectResponse
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('add-round-' . $season, $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $maxRound = $bingo->findMaxRoundForSeason($season);
        $gameRound = $bingo->createEmptyRound($season, $maxRound + 1);

        $this->addFlash('success', sprintf('Round %d de la saison %d créé', $gameRound->getNumber(), $season));

        return $this->redirectToRoute('app_referee_bingo');
    }

    #[Route('/add-season', name: 'app_referee_bingo_add_season', methods: [Request::METHOD_POST])]
    public function addSeason(Request $request, BingoService $bingo): RedirectResponse
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('add-season', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $maxSeason = $bingo->findMaxSeason();
        $newSeason = $maxSeason + 1;
        $bingo->createEmptyRound($newSeason, 1);

        $this->addFlash('success', sprintf('Saison %d créée (round 1)', $newSeason));

        return $this->redirectToRoute('app_referee_bingo');
    }

    #[Route('/fill-gaps/{season}', name: 'app_referee_bingo_fill_gaps', requirements: ['season' => '\d+'], methods: [Request::METHOD_POST])]
    public function fillGaps(Request $request, int $season, BingoService $bingo): RedirectResponse
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('fill-gaps-' . $season, $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

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
            'duplicateCoords' => $bingoDto->duplicateCoords,
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
