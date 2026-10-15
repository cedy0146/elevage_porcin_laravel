<?php

namespace App\Notifications;

use App\Models\FeedType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte de stock critique d'un aliment.
 *
 * Canaux : database = alerte visible dans l'application (l'utilisateur la consulte
 * en retournant sur le tableau de bord sans attendre un e-mail) ; mail = prévenir
 * le responsable hors connexion, par exemple le soir depuis son téléphone.
 * Les deux canaux sont complémentaires : l'un assure la traçabilité interne,
 * l'autre pousse l'information hors du système.
 */
class StockCriticalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FeedType $feedType) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Stock critique : {$this->feedType->name}")
            ->line($this->message())
            ->action('Voir les stocks', url('/api/v1/feed-types'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stock_critical',
            'feed_type_id' => $this->feedType->id,
            'feed_type_name' => $this->feedType->name,
            'farm_id' => $this->feedType->farm_id,
            'quantity' => $this->feedType->quantity,
            'minimum_threshold' => $this->feedType->minimum_threshold,
            'message' => $this->message(),
        ];
    }

    private function message(): string
    {
        return "Alerte : le stock de {$this->feedType->name} est à {$this->feedType->quantity} kg, sous le seuil minimum de {$this->feedType->minimum_threshold} kg.";
    }
}
