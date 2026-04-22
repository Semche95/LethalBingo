<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('IS_AUTHENTICATED')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface      $entityManager
    )
    {
    }

    #[Route('/profile', name: 'app_user_profile')]
    public function index(Request $request): Response
    {
        // Get the current user
        $user = $this->getUser();

        // Create the change password form
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_user_profile');
            }

            // Encode the new password
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $data['plainPassword']
                )
            );

            // Save the changes
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Add a success flash message
            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
