<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string  $memberName;
    public string  $unitName;
    public string  $status;
    public ?string $reason;
    public string  $feeName;
    public string  $feeAmount;
    public string  $contactEmail;
    public string  $loginUrl;

    public function __construct(
        string  $memberName,
        string  $unitName,
        string  $status,
        ?string $reason       = null,
        string  $feeName      = '—',      // ← optional, default '—'
        string  $feeAmount    = '—',      // ← optional, default '—'
        string  $contactEmail = '',
        string  $loginUrl     = ''
    ) {
        $this->memberName   = $memberName;
        $this->unitName     = $unitName;
        $this->status       = $status;
        $this->reason       = $reason;
        $this->feeName      = $feeName;
        $this->feeAmount    = $feeAmount;
        $this->contactEmail = $contactEmail;
        $this->loginUrl     = $loginUrl;
    }

    public function envelope(): Envelope
    {
        $subject = $this->status === 'approved'
            ? '🎉 Your ECA Membership has been Approved!'
            : 'Update on Your ECA Membership Application';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.member-status');
    }

    public function attachments(): array
    {
        return [];
    }
}