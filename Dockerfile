FROM php:8.2-apache

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        curl \
        xml \
        iconv \
        session \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache document root and allow .htaccess override
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && { \
        echo '<Directory /var/www/html/>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/apache2.conf

# Recommended PHP settings for BaseRef (uploads, execution limits)
RUN { \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 80M'; \
        echo 'memory_limit = 512M'; \
        echo 'max_execution_time = 300'; \
        echo 'allow_url_fopen = On'; \
    } > /usr/local/etc/php/conf.d/baseref.ini

# Copy project files
WORKDIR /var/www/html
COPY . /var/www/html/

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
