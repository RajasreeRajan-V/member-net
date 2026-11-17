<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $memberName;
    public string $email;
    public string $password;
    public string $unitName;
    // feeName and feeAmount REMOVED â€” fee is shown in approval email, not here

    public function __construct(
        string $memberName,
        string $email,
        string $password,
        string $unitName,
    ) {
        $this->memberName = $memberName;
        $this->email      = $email;
        $this->password   = $password;
        $this->unitName   = $unitName;
    }

    public function envelope(): Envelope
{
    return new Envelope(
        subject: '📋 MemberNet Registration Received — Under Review',
    );
}

    public function content(): Content
    {
        return new Content(view: 'emails.member-welcome');
    }

    public function attachments(): array
    {
        return [];
    }
}