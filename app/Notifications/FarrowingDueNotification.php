<?php

namespace App\Notifications;

use App\Models\Mating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\RoutesNotifications;

/**
 * Alertes de mise bas prochaine.
 *
 * Canaux : database = suivi dans l'application ; mail = prévenir le
 * responsable hors connexion (transport de démonstration : log).
 */
class FarrowingDueNotification extends Notification implements ShouldQueue
{
    use Queueable, RoutesNotifications;

    public function __construct(public Mating $mating) {}

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
            ->subject('Mise bas prochaine - truie '.$this->mating->sow?->tag)
            ->line("La truie {$this->mating->sow?->tag} est attendue en mise bas le ".$this->mating->expected_farrowing_on?->toDateString().'.')
            ->line('Merci de préparer la maternité.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'mating_id' => $this->mating->id,
            'sow_tag' => $this->mating->sow?->tag,
            'expected_farrowing_on' => $this->mating->expected_farrowing_on?->toDateString(),
            'message' => "Mise bas prochaine pour la truie {$this->mating->sow?->tag} le ".$this->mating->expected_farrowing_on?->toDateString().'.',
        ];
    }
}
