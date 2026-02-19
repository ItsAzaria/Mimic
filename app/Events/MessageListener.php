<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use finfo;
use Illuminate\Support\Str;
use Laracord\Events\Event;

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
            return;
        }

        $content = $message->content ?? '';
        $codeBlocks = $this->collectCodeBlocks($content);
        $attachmentResult = $this->processAttachments($message);

        $files = $attachmentResult['files'];
        $deletedMimeTypes = $attachmentResult['deleted_mime_types'];
        $shouldDeleteOriginal =
            collect($codeBlocks)->where('should_upload', true)->isNotEmpty() ||
            $attachmentResult['should_delete'];

        if (! $shouldDeleteOriginal) {
            return;
        }

        $message->delete();

        $hasUploadableContent = ! empty($files) || collect($codeBlocks)->where('should_upload', true)->isNotEmpty();

        if (! $hasUploadableContent) {
            $this->sendDisallowedContentMessage($message, $deletedMimeTypes);

            return;
        }

        [$codeBlocks, $files] = $this->uploadToPastecord($codeBlocks, $files);
        $responseLines = $this->buildResponseLines($message, $codeBlocks, $files, $content);

        $message->channel->sendMessage(implode("\n", $responseLines));

        $this->sendLogMessage($message, $responseLines);
    }

    private function collectCodeBlocks(string $content): array
    {
        if (! Str::contains($content, '```')) {
            return [];
        }

        $maxCodeBlockSize = \App\Models\Config::get(\App\Models\Config::MAX_CODEBLOCK_SIZE, 1000);
        $codeBlocks = [];

        foreach ($this->extractCodeBlocksWithLanguage($content) as $block) {
            $isTooLarge = Str::length($block['code']) > $maxCodeBlockSize;

            $codeBlocks[] = [
                'content' => $block['code'],
                'should_upload' => $isTooLarge,
                'language' => $block['language'],
                'url' => null,
            ];
        }

        return $codeBlocks;
    }

    private function processAttachments(Message $message): array
    {
        $files = [];
        $deletedMimeTypes = [];
        $shouldDelete = false;
        $mimeDetector = new finfo(FILEINFO_MIME_TYPE);

        foreach ($message->attachments as $attachment) {
            $fileContents = file_get_contents($attachment->url);
            $mimeType = $mimeDetector->buffer($fileContents);
            $mimeRule = \App\Models\Mime::where('mime', $mimeType)->first();

            if (! $mimeRule) {
                $shouldDelete = true;
                $deletedMimeTypes[] = $mimeType;

                continue;
            }

            if ($mimeRule->handling === 'UPLOAD') {
                $files[] = [
                    'name' => $attachment->filename,
                    'file' => $fileContents,
                    'mime' => $mimeType,
                    'url' => null,
                ];
                $shouldDelete = true;
            }
        }

        return [
            'files' => $files,
            'deleted_mime_types' => $deletedMimeTypes,
            'should_delete' => $shouldDelete,
        ];
    }

    private function sendDisallowedContentMessage(Message $message, array $deletedMimeTypes): void
    {
        $mimeList = $deletedMimeTypes ? implode(', ', $deletedMimeTypes) : 'unknown/unsupported';
        $message->channel->sendMessage(
            "Hey {$this->memberMention($message)}, your message contained disallowed content (MIME types: {$mimeList}) and has been deleted."
        );
    }

    private function uploadToPastecord(array $codeBlocks, array $files): array
    {
        $pastecord = new \App\Services\Pastecord;

        foreach ($codeBlocks as $index => $block) {
            if ($block['should_upload']) {
                $codeBlocks[$index]['url'] = $pastecord->upload($block['content']);
            }
        }

        foreach ($files as $index => $file) {
            $files[$index]['url'] = $pastecord->upload($file['file']);
        }

        return [$codeBlocks, $files];
    }

    private function buildResponseLines(Message $message, array $codeBlocks, array $files, string $content): array
    {
        $responseLines = [
            "Hey {$this->memberMention($message)}, your file(s) and/or code block(s) have been uploaded to Pastecord:\n",
        ];

        foreach ($codeBlocks as $index => $block) {
            $lang = $block['language'] ?: '';

            if ($block['should_upload']) {
                $responseLines[] = "- **Code Block[{$index}]: **".($block['url'] ?? 'Failed to upload');

                continue;
            }

            $responseLines[] = "- **Code Block[{$index}]: ** (not uploaded, below is the content)\n```{$lang}\n{$block['content']}\n```";
        }

        foreach ($files as $file) {
            $responseLines[] = "- **File:** {$file['name']}: ".($file['url'] ?? 'Failed to upload');
        }

        $strippedContent = trim($this->stripCodeBlocks($content));
        if ($strippedContent !== '') {
            $responseLines[] = "\n**Message Content:**\n```{$strippedContent}```";
        }

        return $responseLines;
    }

    private function sendLogMessage(Message $message, array $responseLines): void
    {
        $loggingChannelId = \App\Models\Config::get(\App\Models\Config::LOGGING_CHANNEL_ID);
        if (! $loggingChannelId) {
            return;
        }

        $loggingChannel = $this->discord()->getChannel($loggingChannelId);
        if (! $loggingChannel) {
            return;
        }

        $loggingChannel->sendMessage(
            "Message from {$this->memberMention($message)} in <#{$message->channel_id}> was deleted due to disallowed content. Uploaded content:\n\n".
            implode("\n", $responseLines)
        );
    }

    private function memberMention(Message $message): string
    {
        $memberId = $message->member?->id ?? $message->author?->id;

        if (! $memberId) {
            return 'there';
        }

        return "<@{$memberId}>";
    }

    private function extractCodeBlocksWithLanguage(string $string): array
    {
        $codeBlocks = [];

        if (preg_match_all('/```(\w+)?\s*(.*?)```/s', $string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $codeBlocks[] = [
                    'language' => $match[1] ?? '',
                    'code' => trim($match[2]),
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
