<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;
use finfo;

class MessageListener extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_CREATE;

    /**
     * Handle the event.
     */
    public function handle(Message $message, Discord $discord)
    {
        if ($message->member->user->bot) {
            return;
        }

        $attachments = $message->attachments;

        



        $message->attachments->map(function ($attachment) use ($message) {
            $file = file_get_contents($attachment->url);

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($file);

            $message->reply('You sent an attachment with mime type ' . $mimeType . ' and filename ' . $attachment->filename);
        });
    }
}
