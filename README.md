A personal dashboard for managing GitHub repositories, pull requests, and issues. Built with Laravel

## License

MIT

## Icons

- Component: `resources/js/components/Icon.svelte`
- Usage: `<Icon name="plus" size="16" />` or `<Icon name="cross" />`
- Sources: SVG files in `resources/svg` (e.g., `plus.svg`, `cross.svg`).
- Behavior: Loads the matching SVG by name and inlines it; size is controlled via `size` (number or CSS value). Icon color follows `currentColor`.

## Utilities

- Replaying stored incoming webhooks:
  - Replay all: `php artisan webhooks:replay`
  - Replay one by id: `php artisan webhooks:replay 123`
  - Dry run: `php artisan webhooks:replay --dry-run`
  - This re-dispatches the original payload to `POST /api/incoming_hook` with the stored event.
