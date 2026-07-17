<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/referee/team')]
class TeamController extends AbstractController
{
    #[Route('', name: 'app_referee_team')]
    public function index(): Response
    {
        return $this->render('referee/team/index.html.twig');
    }

    #[Route('/add', name: 'app_referee_team_add')]
    public function add(): Response
    {
        $team = new Team();
        $team->addMember(new TeamMember()->setCaptain(true));
        return $this->render('referee/team/form.html.twig', [
            'team' => $team,
            'mode' => 'add',
        ]);
    }

    #[Route('/edit/{id}', name: 'app_referee_team_edit', requirements: ['id' => '\d+'])]
    public function edit(Team $team): Response
    {
        return $this->render('referee/team/form.html.twig', [
            'team' => $team,
            'mode' => 'edit',
        ]);
    }

    #[Route(
        '/delete/{id}',
        name: 'app_referee_team_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST]
    )]
    public function delete(Request $request, EntityManagerInterface $em, Team $team): RedirectResponse
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('delete-team', $submittedToken)) {
            throw new BadRequestHttpException('Token invalid');
        }

        $em->remove($team);
        $em->flush();

        $this->addFlash('success', 'Équipe supprimée');

        return $this->redirectToRoute('app_referee_team');
    }
}
