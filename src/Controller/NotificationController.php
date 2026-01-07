<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notifications')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findByUserOrderedByNewest($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/read/{id}', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        
        // Security check: ensure the notification belongs to the current user
        if ($notification->getUser() !== $user) {
            throw $this->createAccessDeniedException('You can only mark your own notifications as read.');
        }

        $notificationRepository->markAsRead($notification->getId(), $user);

        return $this->redirectToRoute('app_notifications');
    }
}
