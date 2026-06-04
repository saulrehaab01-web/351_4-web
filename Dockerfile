# Use official PHP + Apache image
FROM php:8.1-apache

# Copy project files into Apache web root
COPY . /var/www/html/

# Enable mysqli and PDO MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql
