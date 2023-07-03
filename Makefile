FIX := 0
CI := 0

SRC_DIR := src
TEMP_DIR := temp
TESTS_DIR := tests
TESTS_TEMP_DIR := $(TESTS_DIR)/temp
VENDOR_DIR := vendor
VENDOR_BIN_DIR := $(VENDOR_DIR)/bin

CODE_CHECKER := nette/code-checker
CODE_CHECKER_VERSION := ~3.3.0
CODE_CHECKER_DIR := $(TEMP_DIR)/code-checker

CODING_STANDARD := nette/coding-standard
CODING_STANDARD_VERSION := ~3.3.2
CODING_STANDARD_DIR := $(TEMP_DIR)/coding-standard

COVERALLS := php-coveralls/php-coveralls
COVERALLS_VERSION := ^2.5
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


.PHONY: code-checker-install
code-checker-install: docker-compose-php
ifeq ($(wildcard $(CODE_CHECKER_DIR)/.), )
	$(DOCKER_PHP) composer create-project $(CODE_CHECKER) $(CODE_CHECKER_DIR) \
		$(CODE_CHECKER_VERSION) --no-interaction --no-progress --no-dev
endif

.PHONY: code-checker
code-checker: code-checker-install docker-compose-php
ifeq ($(FIX), 0)
	$(DOCKER_PHP) ./$(CODE_CHECKER_DIR)/code-checker --no-progress \
		--strict-types --eol
else
	$(DOCKER_PHP) ./$(CODE_CHECKER_DIR)/code-checker --no-progress \
		--strict-types --eol --fix
endif


.PHONY: coding-standard-install
coding-standard-install: docker-compose-php
ifeq ($(wildcard $(CODING_STANDARD_DIR)/.), )
	$(DOCKER_PHP) composer create-project $(CODING_STANDARD) \
		$(CODING_STANDARD_DIR) $(CODING_STANDARD_VERSION) --no-interaction \
		--no-progress --no-dev
endif

.PHONY: coding-standard
coding-standard: coding-standard-install docker-compose-php
ifeq ($(FIX), 0)
	$(DOCKER_PHP) ./$(CODING_STANDARD_DIR)/ecs check $(SRC_DIR) $(TESTS_DIR)
else
	$(DOCKER_PHP) ./$(CODING_STANDARD_DIR)/ecs check $(SRC_DIR) $(TESTS_DIR) \
		--fix
endif


.PHONY: phpstan
phpstan: install docker-compose-php
	$(DOCKER_PHP) ./$(VENDOR_BIN_DIR)/phpstan analyse -c phpstan.neon \
		--no-progress


.PHONY: tests
tests: install docker-compose-php
	$(DOCKER_PHP) ./$(VENDOR_BIN_DIR)/tester $(TESTS_DIR) -s -C


.PHONY: tests-coverage-install
tests-coverage-install: docker-compose-php
ifeq ($(wildcard $(COVERALLS_DIR)/.), )
	$(DOCKER_PHP) composer create-project $(COVERALLS) $(COVERALLS_DIR) \
		$(COVERALLS_VERSION) --no-interaction --no-progress --no-dev
endif

.PHONY: tests-coverage
tests-coverage: install tests-coverage-install docker-compose-php
	$(DOCKER_PHP) ./$(VENDOR_BIN_DIR)/tester -p phpdbg $(TESTS_DIR) -s -C \
		--coverage coverage.xml --coverage-src $(SRC_DIR)
ifeq ($(CI), 1)
	$(DOCKER_PHP) git config --global --add safe.directory /app
	$(DOCKER_PHP) ./$(COVERALLS_DIR)/bin/php-coveralls --verbose \
		--config $(TESTS_DIR)/.coveralls.github-actions.yml
else
	$(DOCKER_PHP) ./$(COVERALLS_DIR)/bin/php-coveralls --verbose \
		--config $(TESTS_DIR)/.coveralls.local.yml
endif


.PHONY: clean
clean:
	git clean -xdff $(TEMP_DIR) $(TESTS_TEMP_DIR) \
		$(shell find $(TESTS_DIR) -type d -name output) $(VENDOR_DIR) \
		$(shell ls -Ap | grep -v '/\|composer.lock')


.PHONY: docker-compose-php
docker-compose-php:
	docker-compose up -d php
