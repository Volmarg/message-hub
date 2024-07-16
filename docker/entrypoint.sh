#!/bin/bash

set -e;
service apache2 restart;
service php8.1-fpm start;

echo -e "[DEBUG] Calling install-or-update \n";
cd /var/www/html && ./install-or-update.sh;