FROM php:7.4-apache

# Install ekstensi mysqli (dibutuhkan SLiMS)
RUN docker-php-ext-install mysqli

# Copy semua file ke direktori root Apache
COPY . /var/www/html/

# Set permission (opsional tapi direkomendasikan)
RUN chown -R www-data:www-data /var/www/html/