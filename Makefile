# ======================================================
# Makefile Package
# ======================================================

# --------------------------------------
# Conteneurs Docker
# --------------------------------------
PHP=docker run --rm -v $(PWD):/app -w /app php:8.4-cli
COMPOSER=docker run --rm -v $(PWD):/app -w /app composer:latest

# --------------------------------------
# Setup
# --------------------------------------
install:
	$(COMPOSER) install --dev

update:
	$(COMPOSER) u

# --------------------------------------
# Tools / Quality
# --------------------------------------
phpstan:
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit=512M

phpcs:
	$(PHP) ./vendor/bin/phpcs
phpcs-fixer:
	$(PHP) ./vendor/bin/php-cs-fixer fix
	$(PHP) ./vendor/bin/phpcbf

rector:
	$(PHP) ./vendor/bin/rector

fix-codestyle: rector phpcs-fixer

# --------------------------------------
# Alias pratique
# --------------------------------------
.PHONY:  install phpstan phpcs rector phpcs-fixer fix-codestyle
