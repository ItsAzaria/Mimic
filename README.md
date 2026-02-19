# Mimic

Mimic is a Discord moderation bot built with Laracord.

It enforces MIME rules on attachments and uploads large code/files to Pastecord.

## Features

- Blocks disallowed attachment MIME types.
- Uploads `UPLOAD` MIME attachments to Pastecord and reposts links.
- Uploads code blocks larger than `MAX_CODEBLOCK_SIZE`.
- Optional moderation logging via `LOGGING_CHANNEL_ID`.

## Slash commands


Both require `manage_messages`.

- `/config key:MAX_CODEBLOCK_SIZE value:<number>`
- `/config key:LOGGING_CHANNEL_ID value:<channel_id>`
- `/mime manage add mime:<type> handling:ALLOW|UPLOAD`
- `/mime manage remove mime:<type>`
- `/mime manage view mime:<type>`

## Getting started

1) Install dependencies

```bash
composer install
```

2) Configure environment

Copy and edit `.env`:

```bash
copy .env.example .env
```

Set at minimum:

```dotenv
APP_NAME=Mimic
APP_ENV=development
DISCORD_TOKEN=your_bot_token_here
```

3) Run the bot

```bash
php laracord bot:boot
```

Use `--no-migrate` if you want to skip migrations on boot.

## Dev

Format with Pint:

```bash
vendor/bin/pint
```

## License

This project is licensed under the MIT License. See [LICENSE.md](LICENSE.md).
