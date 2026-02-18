<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use Illuminate\Support\Str;
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
        if ($message->author?->bot || $message->member?->permissions?->manage_messages) {
            // return;
        }

        $files = [];
        $codeBlocks = [];
        $deletedMimeTypes = [];
        $shouldDeleteOriginal = false;

        $content = $message->content ?? '';

        // contains code block
        if (Str::contains($content, '```')) {
            $maxCodeBlockSize = \App\Models\Config::get(\App\Models\Config::MAX_CODEBLOCK_SIZE, 1000);

            foreach ($this->extractCodeBlocksWithLanguage($content) as $block) {
                $isTooLarge = Str::length($block['code']) > $maxCodeBlockSize;

                $codeBlocks[] = [
                    'content' => $block['code'],
                    'should_upload' => $isTooLarge,
                    'language' => $block['language'],
                    'url' => null,
                ];

                if ($isTooLarge) {
                    $shouldDeleteOriginal = true;
                }
            }
        }




        foreach ($message->attachments as $attachment) {
            $fileContents = file_get_contents($attachment->url);
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($fileContents);

            $mimeRule = \App\Models\Mime::where('mime', $mimeType)->first();

            if (!$mimeRule) {
                // Disallowed MIME type
                $shouldDeleteOriginal = true;
                $deletedMimeTypes[] = $mimeType;
                continue;
            }

            // Allowed, check if upload is required
            if ($mimeRule->handling === 'UPLOAD') {
                $files[] = [
                    'name' => $attachment->filename,
                    'file' => $fileContents,
                    'mime' => $mimeType,
                    'url' => null,
                ];
                $shouldDeleteOriginal = true;
            }
        }

        if (!$shouldDeleteOriginal) {
            return;
        }

        $message->delete();

        $hasUploadableContent = count($files) > 0 || collect($codeBlocks)->where('should_upload', true)->isNotEmpty();

        if (!$hasUploadableContent) {
            $mimeList = $deletedMimeTypes ? implode(', ', $deletedMimeTypes) : 'unknown/unsupported';
            $message->channel->sendMessage(
                "Hey <@{$message->member->id}>, your message contained disallowed content (MIME types: {$mimeList}) and has been deleted."
            );
            return;
        }



        $pastecord = new \App\Services\Pastecord();

        foreach ($codeBlocks as &$block) {
            if ($block['should_upload']) {
                $block['url'] = $pastecord->upload($block['content']);
            }
        }

        foreach ($files as &$file) {
            $file['url'] = $pastecord->upload($file['file']);
        }

        $responseLines = ["Hey <@{$message->member->id}>, your file(s) and/or code block(s) have been uploaded to Pastecord:\n"];

        $count = 0;
        foreach ($codeBlocks as &$block) {
            $lang = $block['language'] ? "{$block['language']}" : '';
            if ($block['should_upload']) {
                $responseLines[] = "- **Code Block[{$count}]: **" . ($block['url'] ?? 'Failed to upload');
            } else {
                $responseLines[] = "- **Code Block[{$count}]: ** (not uploaded, below is the content)\n```{$lang}\n{$block['content']}\n```";
            }

            $count++;
        }

        foreach ($files as $file) {
            $responseLines[] = "- **File:** {$file['name']}: " . ($file['url'] ?? 'Failed to upload');
        }

        $strippedContent = trim($this->stripCodeBlocks($content));
        if ($strippedContent) {
            $responseLines[] = "\n**Message Content:**\n```" . $strippedContent . "```";
        }

        $message->channel->sendMessage(implode("\n", $responseLines));

        $loggingChannelId = \App\Models\Config::get(\App\Models\Config::LOGGING_CHANNEL_ID);
        if (!$loggingChannelId) {
            return;
        }

        $loggingChannel = $this->discord()->getChannel($loggingChannelId);
        if (!$loggingChannel) {
            return;
        }

        $loggingChannel->sendMessage(
            "Message from <@{$message->member->id}> in <#{$message->channel_id}> was deleted due to disallowed content. Uploaded content:\n\n" .
            implode("\n", $responseLines)
        );
    }

    function extractCodeBlocksWithLanguage(string $string): array
    {
        $codeBlocks = [];

        if (preg_match_all('/```(\w+)?\s*(.*?)```/s', $string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $codeBlocks[] = [
                    'language' => $match[1] ?? '',
                    'code' => trim($match[2])
                ];
            }
        }

        return $codeBlocks;
    }

    private function stripCodeBlocks(string $string): string
    {
        return preg_replace('/```(?:\w+)?\s*.*?```/s', '', $string);
    }
}
