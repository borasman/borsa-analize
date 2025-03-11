<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/notifications")
 */
class NotificationApiController extends AbstractController
{
    private $notificationRepository;
    private $entityManager;
    private $serializer;
    private $security;
    private $mercureHub;

    public function __construct(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Security $security,
        HubInterface $mercureHub
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->security = $security;
        $this->mercureHub = $mercureHub;
    }

    /**
     * @Route("", name="api_notifications_list", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        
        // Get filter parameters
        $unreadOnly = $request->query->getBoolean('unread_only', false);
        $type = $request->query->get('type');
        $limit = $request->query->getInt('limit', 20);
        $offset = $request->query->getInt('offset', 0);
        
        // Get notifications based on filters
        $notifications = $this->notificationRepository->findByFilters(
            $user,
            $unreadOnly,
            $type,
            $limit,
            $offset
        );
        
        // Get total unread count
        $unreadCount = $this->notificationRepository->countUnread($user);
        
        return $this->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total' => count($notifications),
            'has_more' => count($notifications) === $limit
        ], Response::HTTP_OK, [], ['groups' => 'notification:read']);
    }

    /**
     * @Route("/{id}", name="api_notifications_show", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $notification = $this->notificationRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$notification) {
            return $this->json(['message' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($notification, Response::HTTP_OK, [], ['groups' => 'notification:read']);
    }

    /**
     * @Route("/{id}/mark-as-read", name="api_notifications_mark_read", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $notification = $this->notificationRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$notification) {
            return $this->json(['message' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }
        
        if (!$notification->isIsRead()) {
            $notification->markAsRead();
            $this->entityManager->persist($notification);
            $this->entityManager->flush();
            
            // Get updated unread count
            $unreadCount = $this->notificationRepository->countUnread($user);
            
            // Publish unread count update
            $this->publishUnreadCountUpdate($user->getId(), $unreadCount);
        }
        
        return $this->json($notification, Response::HTTP_OK, [], ['groups' => 'notification:read']);
    }

    /**
     * @Route("/mark-all-as-read", name="api_notifications_mark_all_read", methods={"POST"})
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->security->getUser();
        $unreadNotifications = $this->notificationRepository->findBy([
            'user' => $user,
            'isRead' => false
        ]);
        
        $now = new \DateTime();
        
        foreach ($unreadNotifications as $notification) {
            $notification->setIsRead(true);
            $notification->setReadAt($now);
            $this->entityManager->persist($notification);
        }
        
        $this->entityManager->flush();
        
        // Publish unread count update (now zero)
        $this->publishUnreadCountUpdate($user->getId(), 0);
        
        return $this->json(['message' => 'All notifications marked as read']);
    }

    /**
     * @Route("/{id}", name="api_notifications_delete", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $notification = $this->notificationRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$notification) {
            return $this->json(['message' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }
        
        $wasUnread = !$notification->isIsRead();
        
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
        
        if ($wasUnread) {
            // Get updated unread count
            $unreadCount = $this->notificationRepository->countUnread($user);
            
            // Publish unread count update
            $this->publishUnreadCountUpdate($user->getId(), $unreadCount);
        }
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/delete-all", name="api_notifications_delete_all", methods={"DELETE"})
     */
    public function deleteAll(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        $readOnly = $request->query->getBoolean('read_only', false);
        
        if ($readOnly) {
            $notifications = $this->notificationRepository->findBy([
                'user' => $user,
                'isRead' => true
            ]);
        } else {
            $notifications = $this->notificationRepository->findBy([
                'user' => $user
            ]);
        }
        
        foreach ($notifications as $notification) {
            $this->entityManager->remove($notification);
        }
        
        $this->entityManager->flush();
        
        if (!$readOnly) {
            // Publish unread count update (now zero)
            $this->publishUnreadCountUpdate($user->getId(), 0);
        }
        
        return $this->json(['message' => 'Notifications deleted successfully']);
    }

    /**
     * @Route("/unread-count", name="api_notifications_unread_count", methods={"GET"})
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = $this->security->getUser();
        $unreadCount = $this->notificationRepository->countUnread($user);
        
        return $this->json(['unread_count' => $unreadCount]);
    }

    /**
     * Publish unread count update to Mercure
     */
    private function publishUnreadCountUpdate(int $userId, int $unreadCount): void
    {
        // Create the update
        $update = new Update(
            sprintf('user/%d/notifications', $userId),
            json_encode(['unread_count' => $unreadCount])
        );
        
        // Publish the update
        $this->mercureHub->publish($update);
    }
} 