<?php

namespace App\Mail;

use App\Http\Controllers\Notification\CreatePdfBil;
use App\Http\Controllers\Notification\CreatePdfBill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $subject;
    public $pdf;
    public $pdfReport;


    /**
     * Create a new message instance.
     */

    public function __construct($mailData, $subject, $pdf, $pdfReport = null)
    {
        $this->mailData = $mailData;
        $this->subject = $subject;
        $this->pdf = $pdf;
        $this->pdfReport = $pdfReport;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.book-mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {

        return [
            Attachment::fromData(fn () => $this->pdf->output(), 'Tagihan buku ' . date('F Y', strtotime($this->mailData['bill']->created_at)) . ' ' . $this->mailData['student']->name)
                ->withMime('application/pdf'),
        ];
    }
}
