<?php

namespace App\Controller\Base;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAppController extends AbstractController
{
    private $notifications = [];

    public function encodeNotifications(): string
    {
        return json_encode($this->notifications);
    }

    public function decodeNotifications(Request $request): void
    {
        if ($request->query->has('notifications')) {
            foreach (json_decode($request->query->get('notifications')) as $notification) {
                $type = $notification->type;
                $message = $notification->message;

                $this->notifications[] = [
                    'type' => $type,
                    'message' => $message
                ];
            }

            $request->query->remove('notifications');
        }
    }

    public function addNotification(NotificationType $type, string $message): void
    {
        $this->notifications[] = [
            'type' => $type->value,
            'message' => $message
        ];
    }

    public function removeNotification(int $index): void
    {
        unset($this->notifications[$index]);
    }

    public function clearNotifications(): void
    {
        $this->notifications = [];
    }

    /**
     * @return array
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }
}