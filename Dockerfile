FROM php:8.3-alpine

WORKDIR /app

# katalog na kod + gml
VOLUME /app

EXPOSE 80

CMD ["sh", "-c", "ip addr | grep inet && php -S 0.0.0.0:80 -t /app"]
