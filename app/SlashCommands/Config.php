<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\SlashCommand;

class Config extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'config';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Configuration for the bot.';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [
        [
            'name' => 'key',
            'description' => 'The config key to set or get.',
            'type' => Option::STRING,
            'required' => true,
            'choices' => [
                [
                    'name' => 'Max Codeblock Size',
                    'value' => 'MAX_CODEBLOCK_SIZE',
                ],
                [
                    'name' => 'Logging Channel ID',
                    'value' => 'LOGGING_CHANNEL_ID',
                ]
            ],
        ],
        [
            'name' => 'value',
            'description' => 'The value to set for the config key. If not provided, the current value will be returned.',
            'type' => Option::STRING,
            'required' => false,
        ],
    ];

    /**
     * The permissions required to use the command.
     *
     * @var array
     */
    protected $permissions = ['manage_messages'];

    /**
     * Indicates whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Indicates whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Handle the slash command.
     *
     * @param  \Discord\Parts\Interactions\Interaction  $interaction
     * @return mixed
     */
    public function handle($interaction)
    {
        $interaction->acknowledge();

        $key = $this->value('key');
        $value = $this->value('value');

        if ($value) {
            \App\Models\Config::set($key, $value);

            $interaction->sendFollowUpMessage(
                $this
                    ->message()
                    ->title('Config Updated')
                    ->content("The config key `$key` has been set to `$value`.")
                    ->build()
            );
        } else {
            $currentValue = \App\Models\Config::get($key, 'Not set');

            $interaction->sendFollowUpMessage(
                $this
                    ->message()
                    ->title('Config Value')
                    ->content("The current value of the config key `$key` is `$currentValue`.")
                    ->build()
            );
        }
    }
}
