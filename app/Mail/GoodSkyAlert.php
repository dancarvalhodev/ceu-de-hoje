<?php

namespace App\Mail;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GoodSkyAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Location $location,
        public float $score,
        public int $cloudCover,
        public string $moonName,
        public float $moonIllumination,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "O céu está bom para observar em {$this->location->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.good-sky-alert',
        );
    }
}
