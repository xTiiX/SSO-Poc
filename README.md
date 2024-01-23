# SSO PoC

## Installation

require composer to work ([install composer](https://getcomposer.org/doc/00-intro.md))

1. `git clone`
2. `cd ./sso-php`
3. `composer i`
4. `cp .example.env .env`
5. Modify `.env` with your `client_id`, `client_secret` and Endpoints URL
6. `php -S localhost:8080 -t .`
7. Test it out !