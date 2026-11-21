<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;  // ← AJOUTER CETTE LIGNE
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            $roles = [$form->get('roles')->getData()];
            $user->setRoles($roles);
            
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
    
    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }
    
    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    
    #[Route('/verification-attente', name: 'app_verify_waiting')]
    public function waitingForVerification(): Response
    {
        return $this->render('security/waiting.html.twig');
    }
    
    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $token = $request->query->get('token');
        
        if (!$token) {
            $this->addFlash('error', 'Token de vérification manquant.');
            return $this->redirectToRoute('app_login');
        }
        
        $user = $userRepo->findOneBy(['verificationToken' => $token]);
        
        if (!$user) {
            $this->addFlash('error', 'Token de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }
        
        $now = new \DateTime();
        if ($user->getVerificationTokenExpiresAt() && $user->getVerificationTokenExpiresAt() < $now) {
            $this->addFlash('error', 'Le lien de vérification a expiré.');
            return $this->redirectToRoute('app_login');
        }
        
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        $em->flush();
        
        $this->addFlash('success', 'Votre compte a été activé avec succès !');
        return $this->redirectToRoute('app_login');
    }
    
    #[Route('/get-last-user-email', name: 'get_last_user_email', methods: ['GET'])]
    public function getLastUserEmail(Request $request): JsonResponse
    {
        // ANO-22 : seule l'adresse saisie dans CETTE session d'inscription peut être renvoyée,
        // jamais celle du dernier inscrit en base.
        $email = $request->getSession()->get('last_registered_email');
        if (!$email) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        return $this->json(['email' => $email]);
    }
    
    #[Route('/check-verification-status', name: 'check_verification_status', methods: ['GET'])]
    public function checkVerificationStatus(Request $request, UserRepository $userRepo): JsonResponse
    {
        // ANO-22 : le statut n'est donné que pour le compte créé dans cette session.
        $email = $request->getSession()->get('last_registered_email');
        if (!$email) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $user = $userRepo->findOneBy(['email' => $email]);

        return $this->json(['verified' => $user !== null && $user->isVerified()]);
    }
}