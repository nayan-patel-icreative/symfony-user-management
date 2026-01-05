<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');
        
        // Get the query builder for users
        $queryBuilder = $userRepository->createQueryBuilder('u');
        
        // Add search filter if search term is provided
        if ($search) {
            $queryBuilder->where('u.name LIKE :search OR u.email LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }
        
        // Add date filters
        if ($dateFrom) {
            $dateFromObj = new \DateTime($dateFrom);
            if ($search) {
                $queryBuilder->andWhere('u.createdAt >= :dateFrom');
            } else {
                $queryBuilder->where('u.createdAt >= :dateFrom');
            }
            $queryBuilder->setParameter('dateFrom', $dateFromObj);
        }
        
        if ($dateTo) {
            $dateToObj = new \DateTime($dateTo);
            $dateToObj->setTime(23, 59, 59); // Include the entire day
            if ($search || $dateFrom) {
                $queryBuilder->andWhere('u.createdAt <= :dateTo');
            } else {
                $queryBuilder->where('u.createdAt <= :dateTo');
            }
            $queryBuilder->setParameter('dateTo', $dateToObj);
        }
        
        // Order by creation date (newest first)
        $queryBuilder->orderBy('u.createdAt', 'DESC');
        
        // Paginate the results
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1), // Current page number
            10 // Items per page
        );

        return $this->render('user/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle avatar upload
            $file = $form->get('avatarFile')->getData();
            if ($file) {
                // Validate file
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Please upload a valid image file (JPG, PNG, or WEBP).');
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
                
                if ($file->getSize() > $maxSize) {
                    $this->addFlash('error', 'File size must be less than 5MB.');
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
                
                // Generate unique filename
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                $newFilename = $safeFilename . '_' . uniqid() . '.' . $file->guessExtension();
                
                // Move the file to the uploads directory
                try {
                    $file->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFilename
                    );
                    $user->setAvatar($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading your file. Please try again.');
                    return $this->render('user/new.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'User "' . $user->getName() . '" has been created successfully!');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('The user you are looking for does not exist.');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('The user you are trying to edit does not exist.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle avatar upload
            $file = $form->get('avatarFile')->getData();
            if ($file) {
                // Validate file
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Please upload a valid image file (JPG, PNG, or WEBP).');
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
                
                if ($file->getSize() > $maxSize) {
                    $this->addFlash('error', 'File size must be less than 5MB.');
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
                
                // Generate unique filename
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                $newFilename = $safeFilename . '_' . uniqid() . '.' . $file->guessExtension();
                
                // Move the file to the uploads directory
                try {
                    $file->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFilename
                    );
                    $user->setAvatar($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading your file. Please try again.');
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'User "' . $user->getName() . '" has been updated successfully!');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $userName = $user->getName();
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'User "' . $userName . '" has been deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token. User could not be deleted.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
