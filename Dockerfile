# Etapa 1: Imagem base para PHP e Apache
FROM php:8.2-apache

# Etapa 2: Instalação de dependências do sistema e extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    && docker-php-ext-install intl pdo pdo_pgsql zip opcache

# Etapa 3: Ativar o módulo Apache rewrite
RUN a2enmod rewrite

# Etapa 4: Instalação do Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Etapa 4: Configurar o DocumentRoot para o diretório "public" do Symfony
RUN sed -i 's|/var/www/html|/var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Etapa 7: Instalação do Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Etapa 8: Definir o diretório de trabalho
WORKDIR /var/www/html

# Etapa 9: Copiar o código do projeto Symfony para o container
COPY . .

# Etapa 8: Instalar dependências do Symfony
RUN composer install --optimize-autoloader

# Etapa 10: Expor a porta 80 para o Apache
EXPOSE 80

# Etapa 11: Iniciar o Apache
CMD ["apache2-foreground"]
