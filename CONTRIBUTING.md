# Contributing

Thanks for wanting to make UniSound better. All contributions are welcome:
bug reports, new providers, docs, typo fixes.

## Getting started

1. **Fork** this repository and clone it.
2. Create a feature branch: `git checkout -b feature-name`.
3. Make your changes.
4. Lint locally: `php -l` every file you touched (CI does the same):

   ```bash
   find api lib -name '*.php' -print -exec php -l {} \; ; php -l router.php
   ```
5. Push the branch and open a pull request.

## Code style

- Match the existing style (4 spaces, no tabs; double quotes for PHP strings).
- No unnecessary comments — let the code speak.
- Return proper HTTP status codes (`400`/`404`/`502`) with a JSON `{status, message}` on errors.
- Never commit secrets; the `.env` file and `.history/` snapshots are gitignored and must stay that way.

## Adding a new source

The pipeline is provider-agnostic: extend `lib/helper.php` with a fetcher + parser,
add a case in `api/list.php`, register the route in `vercel.json` and `router.php`,
then document the endpoint in `README.md`.