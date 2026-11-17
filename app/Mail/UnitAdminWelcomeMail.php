<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnitAdminWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $adminName;
    public string $email;
    public string $password;
    public string $unitName;
    public string $districtName;

    public function __construct(
        string $adminName,
        string $email,
        string $password,
        string $unitName,
        string $districtName,
    ) {
        $this->adminName    = $adminName;
        $this->email        = $email;
        $this->password     = $password;
        $this->unitName     = $unitName;
        $this->districtName = $districtName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔑 ECA Unit Admin Account Created',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.unit-admin-welcome');
    }

    public function attachments(): array
    {
        return [];
    }
}