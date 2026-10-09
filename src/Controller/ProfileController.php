<?php

namespace App\Controller;

use App\Entity\User;
use App\EventListener\SessionIdleTimeoutListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;

#[Route('/user')]
#[IsGranted('IS_AUTHENTICATED')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_user_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    /**
     * Logs out every other browser: their sessions and remember-me cookies hold the
     * old session version. The current session is saved with the new one at the end
     * of this request, so it stays logged in.
     */
    #[Route('/logout-other-sessions', name: 'app_user_logout_other_sessions', methods: [Request::METHOD_POST])]
    public function logoutOtherSessions(
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        RememberMeHandlerInterface $rememberMe,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('logout-other-sessions', $request->getPayload()->getString('token'))) {
            throw new BadRequestHttpException('Token invalid');
        }

        $user->invalidateOtherSessions();
        $em->flush();

        // The remember-me cookie of this browser is signed with the old version too.
        if ($request->getSession()->get(SessionIdleTimeoutListener::REMEMBERED)) {
            $rememberMe->createRememberMeCookie($user);
        }

        $this->addFlash('success', 'Les autres sessions ont été déconnectées.');

        return $this->redirectToRoute('app_user_profile');
    }
}
