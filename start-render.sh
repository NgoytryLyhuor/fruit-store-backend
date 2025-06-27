#!/usr/bin/env bash
# Start the PHP server
php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
