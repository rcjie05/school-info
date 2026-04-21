FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2

# Install Apache + PHP clean with no conflicts
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysqli \
    php8.1-pdo \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-curl \
    php8.1-zip \
    libapache2-mod-php8.1 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable modules
RUN a2enmod rewrite php8.1 headers

# Copy Apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

# Copy PHP ini
COPY docker/php.ini /etc/php/8.1/apache2/conf.d/custom.ini

# Copy project files
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
