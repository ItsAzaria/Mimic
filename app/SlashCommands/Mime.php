<?php

namespace App\SlashCommands;

use App\Traits\SlashCommandHelpers;
use Discord\Parts\Interactions\Command\Option;
use Laracord\Commands\SlashCommand;

class Mime extends SlashCommand
{
    use SlashCommandHelpers;

    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'mime';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Mime slash command.';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [
        [
            'name' => 'manage',
            'description' => 'Add or Remove rules for handling mime types.',
            'type' => Option::SUB_COMMAND_GROUP,
            'options' => [
                [
                    'name' => 'add',
                    'description' => 'Add a rule for handling a mime type.',
                    'type' => Option::SUB_COMMAND,
                    'options' => [
                        [
                            'name' => 'mime',
                            'description' => 'The mime type to add a rule for.',
                            'type' => Option::STRING,
                            'required' => true,
                        ],
                        [
                            'name' => 'handling',
                            'description' => 'How to handle the mime type.',
                            'type' => Option::STRING,
                            'required' => true,
                            'choices' => [
                                ['name' => 'Allow', 'value' => 'ALLOW'],
                                ['name' => 'Upload', 'value' => 'UPLOAD'],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'remove',
                    'description' => 'Remove a rule for handling a mime type.',
                    'type' => Option::SUB_COMMAND,
                    'options' => [
                        [
                            'name' => 'mime',
                            'description' => 'The mime type to remove the rule for.',
                            'type' => Option::STRING,
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'name' => 'view',
                    'description' => 'View the current rules for handling mime types.',
                    'type' => Option::SUB_COMMAND,
                    'options' => [
                        [
                            'name' => 'mime',
                            'description' => 'The mime type to view the rule for.',
                            'type' => Option::STRING,
                            'required' => true,
                        ],
                    ],
                ],
            ],
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

        $operation = collect(['add', 'remove', 'view'])
            ->first(fn ($action) => $this->value("manage.$action.mime") !== null);

        if (! $operation) {
            return $this->replyWithError($interaction, 'Invalid Operation', 'No valid operation provided.');
        }

        $method = 'handle'.ucfirst($operation);

        if (method_exists($this, $method)) {
            return $this->$method($interaction);
        }
    }

    protected function handleAdd($interaction)
    {
        $mime = $this->value('manage.add.mime');
        $handling = $this->value('manage.add.handling');

        \App\Models\Mime::updateOrCreate(
            ['mime' => $mime],
            ['handling' => $handling]
        );

        return $this->reply(
            $interaction,
            'Mime Rule',
            "The handling rule for mime type `$mime` is set to `$handling`."
        );
    }

    protected function handleRemove($interaction)
    {
        $mime = $this->value('manage.remove.mime');

        \App\Models\Mime::where('mime', $mime)->delete();

        return $this->reply(
            $interaction,
            'Mime Rule Removed',
            "The handling rule for mime type `$mime` has been removed."
        );
    }

    protected function handleView($interaction)
    {
        $mime = $this->value('manage.view.mime');

        $rule = \App\Models\Mime::where('mime', $mime)->first();

        if (! $rule) {
            return $this->replyWithError(
                $interaction,
                'No Rule Found',
                "No rule found for mime type `$mime`."
            );
        }

        return $this->reply(
            $interaction,
            'Mime Rule',
            "The handling rule for mime type `$mime` is `$rule->handling`."
        );
    }
}
