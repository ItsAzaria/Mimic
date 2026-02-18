<?php

namespace App\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Laracord\Commands\SlashCommand;

class Mime extends SlashCommand
{
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
                ]
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

        $actions = ['add', 'remove', 'view'];

        $operation = null;

        foreach ($actions as $action) {
            if ($this->value("manage.$action.mime") !== null) {
                $operation = $action;
                break;
            }
        }

        switch ($operation) {
            case 'add':
                $mime = $this->value("manage.add.mime");
                $handling = $this->value("manage.add.handling");

                $this->console()->log("the handling is $handling for mime $mime");

                \App\Models\Mime::updateOrCreate(
                    ['mime' => $mime],
                    ['handling' => $handling]
                );

                $interaction->sendFollowUpMessage(
                    $this
                        ->message()
                        ->title('Mime Rule')
                        ->content("The handling rule for mime type `$mime` is set to `$handling`.")
                        ->build()
                );

                break;
            case 'remove':
                $mime = $this->value("manage.remove.mime");

                \App\Models\Mime::where('mime', $mime)->delete();

                $interaction->sendFollowUpMessage(
                    $this
                        ->message()
                        ->title('Mime Rule Removed')
                        ->content("The handling rule for mime type `$mime` has been removed.")
                        ->build()
                );

                break;
            case 'view':
                $mime = $this->value("manage.view.mime");

                $rule = \App\Models\Mime::where('mime', $mime)->first();

                if (!$rule) {
                    $interaction->sendFollowUpMessage(
                        $this
                            ->message()
                            ->title('No Rule Found')
                            ->content("No rule found for mime type `$mime`.")
                            ->build()
                    );

                } else {

                    $interaction->sendFollowUpMessage(
                        $this
                            ->message()
                            ->title('Mime Rule')
                            ->content("The handling rule for mime type `$mime` is `$rule->handling`.")
                            ->build()
                    );
                }

                break;
        }
    }
}