<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $memberName;
    public string $membershipId;
    public string $unitName;
    public string $phone;

    public function __construct(
        string $memberName,
        string $membershipId,
        string $unitName,
        string $phone,
    ) {
        $this->memberName   = $memberName;
        $this->membershipId = $membershipId;
        $this->unitName     = $unitName;
        $this->phone        = $phone;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Your Member Net Membership has been Approved!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.member-approved');
    }

    public function attachments(): array
    {
        return [];
    }
}