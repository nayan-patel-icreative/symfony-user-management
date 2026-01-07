<?php

namespace App\Controller;

use App\Entity\AuthUser;
use App\Entity\Notification;
use App\Form\LoginFormType;
use App\Form\RegistrationFormType;
use App\Repository\AuthUserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        AuthUserRepository $authUserRepository,
        EmailService $emailService,
        TranslatorInterface $translator
    ): Response {
        // Redirect if user is already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_index');
        }

        $authUser = new AuthUser();
        $form = $this->createForm(RegistrationFormType::class, $authUser);
        $form->handleRequest($request);

        // Debug: Check if form was submitted
        if ($form->isSubmitted()) {
            // Debug: Check form validity
            // dump($form->getData());
            // dump($form->getErrors(true, true));
            // die;
            if ($form->isValid()) {
                // Debug: Check what we're getting from the form
                $plainPasswordField = $form->get('plainPassword');
                $firstPassword = $plainPasswordField->get('first')->getData();
                $secondPassword = $plainPasswordField->get('second')->getData();
                
                // Debug output
                error_log('Plain password field exists: ' . ($plainPasswordField ? 'YES' : 'NO'));
                error_log('First password: ' . ($firstPassword ?? 'NULL'));
                error_log('Second password: ' . ($secondPassword ?? 'NULL'));
                
                // Get plain password data from RepeatedType
                $plainPassword = $firstPassword;
                
                // Manual password validation
                if (empty($plainPassword)) {
                    error_log('Password validation failed - empty');
                    $this->addFlash('error', $translator->trans('form.password_required'));
                    return $this->render('auth/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }
                
                if (strlen($plainPassword) < 6) {
                    error_log('Password validation failed - too short');
                    $this->addFlash('error', $translator->trans('form.password_min_length', ['{{ limit }}' => 6]));
                    return $this->render('auth/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }
                
                // Check if email already exists
                $existingUser = $authUserRepository->findByEmail($authUser->getEmail());
                if ($existingUser) {
                    $this->addFlash('error', $translator->trans('register.email_exists'));
                    return $this->render('auth/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }

                // Encode the plain password
                $authUser->setPassword(
                    $userPasswordHasher->hashPassword(
                        $authUser,
                        $plainPassword
                    )
                );

                $authUser->setRoles(['ROLE_USER']);

                $entityManager->persist($authUser);
                $entityManager->flush();

                // Create welcome notification
                $welcomeNotification = new Notification();
                $welcomeNotification->setTitle('Welcome!');
                $welcomeNotification->setMessage('Your account has been created successfully.');
                $welcomeNotification->setIsRead(false);
                $welcomeNotification->setUser($authUser);
                $welcomeNotification->setCreatedAt(new \DateTimeImmutable());
                
                $entityManager->persist($welcomeNotification);
                $entityManager->flush();

                $emailSent = $emailService->sendWelcomeEmail($authUser->getEmail(), $authUser->getName());
                
                if ($emailSent) {
                    $this->addFlash('success', $translator->trans('register.account_created_success'));
                } else {
                    $this->addFlash('warning', $translator->trans('register.account_created_no_email'));
                }

                return $this->redirectToRoute('app_login');
            } else {
                // Form is submitted but not valid - errors will be shown in template
                error_log('Form validation failed');
                $this->addFlash('error', $translator->trans('register.form_errors'));
            }
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect if user is already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method should never be reached!');
    }
}
