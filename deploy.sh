#!/usr/bin/env bash

composer install --no-dev
composer update --no-dev
composer dumpautoload -o
> ./storage/logs/lumen.log

chown www-data:www-data -R .
chmod 777 -R ./storage/logs/
chmod 777 -R ./storage/temp/
chmod 777 ./history.txt