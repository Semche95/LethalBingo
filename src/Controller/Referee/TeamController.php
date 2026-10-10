<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Service\CsrfGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/referee/team')]
#[IsGranted('ROLE_REFEREE')]
class TeamController extends AbstractController
{
    // With a team, the list opens its modal on load.
    #[Route('/{id}', name: 'app_referee_team', requirements: ['id' => '\d+'], defaults: ['id' => null])]
    public function index(?Team $team = null): Response
    {
        return $this->render('referee/team/index.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/add', name: 'app_referee_team_add')]
    public function add(Request $request): Response
    {
        $team = new Team();
        $team->addMember(new TeamMember()->setCaptain(true));

        return $this->renderTeamForm($request, $team, 'add');
    }

    #[Route('/edit/{id}', name: 'app_referee_team_edit', requirements: ['id' => '\d+'])]
    public function edit(Team $team, Request $request): Response
    {
        return $this->renderTeamForm($request, $team, 'edit');
    }

    // Teams are only managed in the modal: only the modal loader may ask for this bare form.
    private function renderTeamForm(Request $request, Team $team, string $mode): Response
    {
        if (!$request->headers->has('X-Fragment-Request')) {
            throw $this->createNotFoundException();
        }

        return $this->render('referee/team/_fragment.html.twig', [
            'team' => $team,
            'mode' => $mode,
        ]);
    }

    #[Route(
        '/delete/{id}',
        name: 'app_referee_team_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST]
    )]
    public function delete(Request $request, EntityManagerInterface $em, Team $team, CsrfGuard $csrfGuard): RedirectResponse
    {
        $csrfGuard->assertValid('delete-team', $request->getPayload()->getString('token'));

        if (!$team->getGames()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer une équipe qui a des parties');

            return $this->redirectToRoute('app_referee_team');
        }

        $em->remove($team);
        $em->flush();

        $this->addFlash('success', 'Équipe supprimée');

        return $this->redirectToRoute('app_referee_team');
    }
}
