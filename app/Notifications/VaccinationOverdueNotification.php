<?php

namespace App\Notifications;

use App\Models\HealthEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\RoutesNotifications;

/**
 * Alertes de retard sanitaire.
 *
 * Canaux : database = l'alerte reste visible dans l'application pour le
 * responsable ; mail = prévenir le responsable même hors connexion (le
 * transport de démonstration est log, voir MAIL_MAILER dans .env).
 */
class VaccinationOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable, RoutesNotifications;

    public function __construct(public HealthEvent $event) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Retard d\'acte sanitaire - '.$this->event->pigLot?->code)
            ->line("L'acte sanitaire « {$this->event->name} » du lot {$this->event->pigLot?->code} prévu le ".$this->event->scheduled_on?->toDateString().' n\'a pas été effectué.')
            ->line('Merci de programmer sa réalisation au plus vite.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'health_event_id' => $this->event->id,
            'pig_lot_id' => $this->event->pig_lot_id,
            'lot' => $this->event->pigLot?->code,
            'name' => $this->event->name,
            'scheduled_on' => $this->event->scheduled_on?->toDateString(),
            'message' => "Retard d'acte sanitaire : {$this->event->name} (lot {$this->event->pigLot?->code}) prévu le ".$this->event->scheduled_on?->toDateString().'.',
        ];
    }
}
