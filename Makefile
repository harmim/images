# Author: Dominik Harmim <harmim6@gmail.com>

FIX := 0
CI := 0

SRC_DIR := src
TEMP_DIR := temp
TESTS_DIR := tests
TESTS_TEMP_DIR := $(TESTS_DIR)/temp
VENDOR_DIR := vendor
VENDOR_BIN_DIR := $(VENDOR_DIR)/bin

CODE_CHECKER := nette/code-checker
CODE_CHECKER_VERSION := ~3.2.3
CODE_CHECKER_DIR := $(TEMP_DIR)/code-checker

COVERALLS := php-coveralls/php-coveralls
COVERALLS_VERSION := ^2.5.3
COVERALLS_DIR := $(TEMP_DIR)/coveralls

ifeq ($(CI), 1)
	CI_DOCKER_FLAGS := -T
endif
DOCKER_PHP := docker-compose exec $(CI_DOCKER_FLAGS) php


.PHONY: install
install: composer


.PHONY: composer
composer: docker-compose-php
	$(DOCKER_PHP) composer install --no-interaction --no-progress


.PHONY: code-checker
code-checker: code-checker-install code-checker-run

.PHONY: code-checker-install
code-checker-install: docker-compose-php
ifeq ($(wildcard $(CODE_CHECKER_DIR)/.), )
	$(DOCKER_PHP) composer create-project $(CODE_CHECKER) $(CODE_CHECKER_DIR) $(CODE_CHECKER_VERSION) --no-interaction \
		--no-progress --no-dev
endif

.PHONY: code-checker-run
code-checker-run: docker-compose-php
ifeq ($(FIX), 0)
	$(DOCKER_PHP) ./$(CODE_CHECKER_DIR)/code-checker --no-progress --strict-types --eol
else
	$(DOCKER_PHP) ./$(CODE_CHECKER_DIR)/code-checker --no-progress --strict-types --eol --fix
endif


.PHONY: tests
tests: install docker-compose-php
	$(DOCKER_PHP) ./$(VENDOR_BIN_DIR)/tester $(TESTS_DIR) -s -C


.PHONY: tests-coverage
tests-coverage: tests-coverage-install tests-coverage-run

.PHONY: tests-coverage-install
tests-coverage-install: docker-compose-php
ifeq ($(wildcard $(COVERALLS_DIR)/.), )
	$(DOCKER_PHP) composer create-project $(COVERALLS) $(COVERALLS_DIR) $(COVERALLS_VERSION) --no-interaction \
		--no-progress --no-dev
endif

.PHONY: tests-coverage-run
tests-coverage-run: install docker-compose-php
	$(DOCKER_PHP) ./$(VENDOR_BIN_DIR)/tester -p phpdbg $(TESTS_DIR) -s -C --coverage coverage.xml \
		--coverage-src $(SRC_DIR)
ifeq ($(CI), 1)
	$(DOCKER_PHP) git config --global --add safe.directory /app
	$(DOCKER_PHP) ./$(COVERALLS_DIR)/bin/php-coveralls --verbose --config $(TESTS_DIR)/.coveralls.github-actions.yml
else
	$(DOCKER_PHP) ./$(COVERALLS_DIR)/bin/php-coveralls --verbose --config $(TESTS_DIR)/.coveralls.local.yml
endif


.PHONY: clean
clean:
	git clean -xdff $(TEMP_DIR) $(TESTS_TEMP_DIR) $(shell find $(TESTS_DIR) -type d -name output) $(VENDOR_DIR) \
		$(shell ls -Ap | grep -v '/\|composer.lock')


.PHONY: docker-compose-php
docker-compose-php:
	docker-compose up -d php
