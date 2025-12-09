# 1. Use PHP with Apache
FROM php:8.1-apache

# 2. Install git and unzip (Required for Composer)
RUN apt-get update && apt-get install -y git unzip

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set working directory
WORKDIR /var/www/html

# 5. Copy all files from your computer to Render
COPY . .

# --- FIX STARTS HERE ---
# 6. Force delete the 'vendor' folder (in case Windows files were copied)
RUN rm -rf vendor

# 7. Install the library fresh for Linux
RUN composer install --no-dev --optimize-autoloader
# --- FIX ENDS HERE ---

# 8. Fix permissions so Apache can read the files
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# 9. Open port 80
EXPOSE 80