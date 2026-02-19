<?php

namespace App\Traits;

trait SlashCommandHelpers
{
    protected function reply($interaction, string $title, string $content)
    {
        $interaction->sendFollowUpMessage(
            $this->message()
                ->title($title)
                ->content($content)
                ->build()
        );
    }

    protected function replyWithError($interaction, string $title, string $content)
    {
        $interaction->sendFollowUpMessage(
            $this->message()
                ->title($title)
                ->content($content)
                ->color(0xFF0000)
                ->build()
        );
    }

    protected function setConfig($interaction, string $key, $value)
    {
        $oldValue = \App\Models\Config::get($key);

        \App\Models\Config::set($key, $value);

        $message = $oldValue === null
            ? "The config key `$key` has been set to `$value`."
            : "The config key `$key` has been updated from `$oldValue` to `$value`.";

        return $this->reply($interaction, 'Config Updated', $message);
    }

    protected function viewConfig($interaction, string $key)
    {
        $currentValue = \App\Models\Config::get($key);

        if ($currentValue === null) {
            return $this->reply(
                $interaction,
                'Config Value',
                "The config key `$key` is not set."
            );
        }

        return $this->reply(
            $interaction,
            'Config Value',
            "The current value of the config key `$key` is `$currentValue`."
        );
    }
}
