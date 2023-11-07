<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** Create a new message instance. */
    public function __construct(
        private readonly string $message,
    ) {

    }

    /** Get the message envelope. */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Mail',
        );
    }

    /** Get the message content definition. */
    public function content(): Content
    {
        return new Content(
            markdown: 'mails.general-message',
            with: [
                'content' => $this->message,
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
