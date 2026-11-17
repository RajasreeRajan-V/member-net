<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DistrictAdminWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $adminName,
        public string $email,
        public string $password,
        public string $districtName,
        public string $stateName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your District Admin Account — ' . $this->districtName,
        );
    }

    public function content(): Content
    {
        // Create resources/views/emails/district-admin-welcome.blade.php
        // (copy the structure of your existing unit-admin-welcome view;
        // it receives the same variable names below).
        return new Content(
            view: 'emails.district-admin-welcome',
            with: [
                'adminName'    => $this->adminName,
                'email'        => $this->email,
                'password'     => $this->password,
                'districtName' => $this->districtName,
                'stateName'    => $this->stateName,
            ],
        );
    }
}