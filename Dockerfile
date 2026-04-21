FROM dunglas/frankenphp

RUN install-php-extensions pdo_mysql mysqli

COPY . /app/public

ENV DOCUMENT_ROOT=/app/public