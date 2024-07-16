#!/bin/bash

# exit if any command will fail
set -e;

if [ -f "./.is-installed" ]; then
  printf "Project is already installed! \n"
  exit 1;
fi;

if [  -f "./.env" ]; then
  ENV_NEW_NAME=".env.backup_$(date +"%Y_%m_%d__%H_%M_%S")_$(date | shasum -a 256 | sed  's/[ ]-//g' | xargs)"
  printf "Backing up .env file under ${ENV_NEW_NAME} \n";
  cp "./.env" "${ENV_NEW_NAME}";
fi

printf "SETTING .env file \n";
cp "./.env.default" "./.env";

printf "INSTALLING COMPOSER PACKAGES \n";
composer install --ignore-platform-reqs;

printf "CREATING DATABASE \n";
php -d xdebug.mode=off bin/console doctrine:database:create;

printf "SETTING DATABASE TABLES \n";
php -d xdebug.mode=off bin/console doctrine:migrations:migrate --no-interaction;

printf "PREPARING CACHE \n";
php -d xdebug.mode=off bin/console cache:clear && php -d xdebug.mode=off bin/console cache:warmup;

printf "SETTING DIRS RIGHTS \n";
chmod 777 ./var -R && chown www-data:www-data ./var -R;

printf "BUILDING TRANSLATION FILES \n";
bin/console mh:assets:build-frontend-translation-file

touch "./.is-installed";
printf "DONE \n"