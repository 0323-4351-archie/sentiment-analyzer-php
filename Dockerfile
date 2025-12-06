# Use an official PHP with Apache image
FROM php:8.1-apache

# Install unzip and git (needed for Composer)
RUN apt-get update && apt-get install -y unzip git

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy all your files into the container
COPY . .

# Run Composer Install to download the ML library
RUN composer install --no-dev --optimize-autoloader

# Allow Apache to write to the folder (permission fix)
RUN chown -R www-data:www-data /var/www/html

# Tell Render to use port 80
EXPOSE 80