#!/bin/bash
PORT="${PORT:-80}"
echo "Starting Apache on port $PORT..."

# Update ports
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# Start Apache
source /etc/apache2/envvars
exec apache2 -D FOREGROUND
