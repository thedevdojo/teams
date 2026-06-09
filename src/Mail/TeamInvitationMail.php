<?php

namespace Devdojo\Teams\Mail;

use Devdojo\Teams\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TeamInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Invitation to join the :team team', ['team' => $this->invitation->team->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'teams::mail.team-invitation',
            with: [
                'team' => $this->invitation->team,
                'acceptUrl' => URL::signedRoute('teams.invitations.accept', [
                    'invitation' => $this->invitation->getKey(),
                ]),
            ],
        );
    }
}
