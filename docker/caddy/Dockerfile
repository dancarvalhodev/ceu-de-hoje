FROM caddy:2.11.7-alpine

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile

# "app" = the production php image, given by compose.prod.yaml (additional_contexts).
# Copying public/ from it keeps the Vite files identical in both images.
COPY --from=app /var/www/html/public /var/www/html/public
