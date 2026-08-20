#!/bin/bash

set -e

cd /var/www/html

php artisan module:enable TripoliCustomizations --no-interaction
