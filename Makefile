SHELL := /bin/bash

# bin file definitions
PHPUNIT=php -d zend.enable_gc=0  vendor-bin/phpunit/vendor/bin/phpunit
PHP_CS_FIXER=php -d zend.enable_gc=0 vendor-bin/codestyle/vendor/bin/php-cs-fixer
PHAN=php -d zend.enable_gc=0 vendor-bin/phan/vendor/bin/phan
PHPSTAN=php -d zend.enable_gc=0 vendor-bin/phpstan/vendor/bin/phpstan
COMPOSER_BIN := $(shell command -v composer 2> /dev/null)

.DEFAULT_GOAL := help

.DEFAULT_GOAL := help

help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

##
## Entrypoints
##--------------------------------------

.PHONY: dev
dev:                       ## Initialize dev environment
dev: install-deps

##
## Tests
##--------------------------------------

.PHONY: unit
unit:             ## Run php unit tests
unit: vendor-bin/phpunit/vendor
	$(PHPUNIT)  --testsuite unit

.PHONY: functional
functional:      ## Run php functional tests
functional: vendor-bin/phpunit/vendor
	$(PHPUNIT)  --testsuite functional


.PHONY: style
style:            ## Run php-cs-fixer and check owncloud code-style
style: vendor-bin/codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --diff-format udiff --allow-risky yes --dry-run

.PHONY: style-fix
style-fix:        ## Run php-cs-fixer and fix code style issues
style-fix: vendor-bin/codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --diff-format udiff --allow-risky yes

.PHONY: phan
phan:             ## Run phan
phan: vendor-bin/phan/vendor
	$(PHAN) --config-file .phan/config.php --require-config-exists

.PHONY: phpstan
phpstan:          ## Run phpstan
phpstan: vendor-bin/phpstan/vendor
	$(PHPSTAN) analyse --memory-limit=4G  --no-progress --level=5

##
## Dependency management
##--------------------------------------

.PHONY: check-composer
check-composer:
	ifndef COMPOSER_BIN
		$(error composer is not available on your system, please install composer)
	endif

.PHONY: install-deps
install-deps:              ## install dependencies
install-deps: install-php-deps

composer.lock: composer.json
	@echo composer.lock is not up to date.

.PHONY: install-php-deps
install-php-deps:          ## Install PHP dependencies
install-php-deps: vendor composer.json composer.lock

vendor: composer.lock
	$(COMPOSER_BIN) install --no-dev

vendor/bamarni/composer-bin-plugin: composer.lock
	$(COMPOSER_BIN) install

vendor-bin/phpunit/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/phpunit/composer.lock
	$(COMPOSER_BIN) bin phpunit install --no-progress

vendor-bin/phpunit/composer.lock: vendor-bin/phpunit/composer.json
	@echo phpunit composer.lock is not up to date.

vendor-bin/codestyle/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/codestyle/composer.lock
	$(COMPOSER_BIN) bin codestyle install --no-progress

vendor-bin/codestyle/composer.lock: vendor-bin/codestyle/composer.json
	@echo owncloud-codestyle composer.lock is not up to date.

vendor-bin/phan/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/phan/composer.lock
	$(COMPOSER_BIN) bin phan install --no-progress

vendor-bin/phan/composer.lock: vendor-bin/phan/composer.json
	@echo phan composer.lock is not up to date.

vendor-bin/phpstan/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/phpstan/composer.lock
	$(COMPOSER_BIN) bin phpstan install --no-progress

vendor-bin/phpstan/composer.lock: vendor-bin/phpstan/composer.json
	@echo phpstan composer.lock is not up to date.