# Tiny tool for generate static index.html

Save response body to `/abc/index.html` from `http://example.jp/abc` when set `/abc/` in `$path_list`.

Use it when need a small static cache.

## AT FIRST (every time in use)

PLEASE BACKUP YOUR SITE.

## description of files.

- `public_html/staticker.php` main app
- `public_html/index.php` is mock app for testing, not need.

## usage

- generate your hashed password by `php -r 'echo password_hash("default_password_1234!", PASSWORD_DEFAULT).PHP_EOL;'`
- edit `staticker.php`
  - set `USER_NAME` and `HASHED_PASSWORD`
  - set `ALLOW_IP_LIST`
  - edit `$path_list`
- put on server document root dir with random file name (ex: `statick_generate_tool_sdfjiafofjeafcac.php`).

## lisence

MIT
