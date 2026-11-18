<?php

declare(strict_types=1);

namespace App\Controller\Referee;

use App\Entity\Rule;
use App\Service\CsrfGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/referee/rule')]
#[IsGranted('ROLE_REFEREE')]
class RuleController extends AbstractController
{
    #[Route('', name: 'app_referee_rule')]
    public function index(): Response
    {
        return $this->render('referee/rule/index.html.twig');
    }

    #[Route('/add', name: 'app_referee_rule_add')]
    public function add(Request $request): Response
    {
        return $this->renderRuleForm($request, new Rule(), 'add');
    }

    #[Route('/edit/{id}', name: 'app_referee_rule_edit', requirements: ['id' => '\d+'])]
    public function edit(Rule $rule, Request $request): Response
    {
        return $this->renderRuleForm($request, $rule, 'edit');
    }

    // Rules are only managed in the modal: only the modal loader may ask for this bare form.
    private function renderRuleForm(Request $request, Rule $rule, string $mode): Response
    {
        if (!$request->headers->has('X-Fragment-Request')) {
            throw $this->createNotFoundException();
        }

        return $this->render('referee/rule/_fragment.html.twig', [
            'rule' => $rule,
            'mode' => $mode,
        ]);
    }

    #[Route(
        '/delete/{id}',
        name: 'app_referee_rule_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST]
    )]
    public function delete(Request $request, EntityManagerInterface $em, Rule $rule, CsrfGuard $csrfGuard): RedirectResponse
    {
        $csrfGuard->assertValid('delete-rule', $request->getPayload()->getString('token'));

        if ($rule->isComputed()) {
            $this->addFlash('error', 'Impossible de supprimer une règle calculée par l\'application');

            return $this->redirectToRoute('app_referee_rule');
        }

        $em->remove($rule);
        $em->flush();

        $this->addFlash('success', 'Règle supprimée');

        return $this->redirectToRoute('app_referee_rule');
    }
}
