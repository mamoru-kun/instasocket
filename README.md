# InstaSocket

## How it works
It gets posts from Instagram using your Instagram App's *access token* and requests them again and again every 10 seconds (you can change it in `./Config.php`). If there's changes in posts collection the server sends it to all of the connected clients (your website visitors).

## Requirements
- PHP 7+
- Composer
- A POSIX compatible operating system (Linux, OSX, BSD)
- POSIX and PCNTL extensions for PHP

## Installation

- Clone the project to your server
- `composer install`
- Make `Config.php` file by copying `Example.Config.php` file
- Register the Instagram app and get its access token
- Add your access token in `Config.php` in `ACCESS_TOKEN` constant.
- `php app.php start`
