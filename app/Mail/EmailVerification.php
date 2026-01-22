<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $username;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code, string $username = 'there')
    {
        $this->code = $code;
        $this->username = $username;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'PXL'),
            subject: $this->username . ', your verification code is ' . $this->code,
        );
    }

    /**
     * Get the message content definition.
     * Plain-text part improves deliverability (many filters penalize HTML-only).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification',
            text: 'emails.verification-text',
            with: [
                'code' => $this->code,
                'username' => $this->username,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
