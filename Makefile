# customization

PACKAGE_NAME = icanboogie/http
PACKAGE_VERSION = 4.0
PHPUNIT = vendor/bin/phpunit
# do not edit the following lines

.PHONY: usage
usage:
	@echo "test:  Runs the test suite.\ndoc:   Creates the documentation.\nclean: Removes the documentation, the dependencies and the Composer files."

vendor:
	@COMPOSER_ROOT_VERSION=$(PACKAGE_VERSION) composer install

.PHONY: update
update:
	@COMPOSER_ROOT_VERSION=$(PACKAGE_VERSION) composer update

.PHONY: test-dependencies
test-dependencies: vendor test-cleanup

.PHONY: test
test: test-dependencies
	@$(PHPUNIT)

.PHONY: test-coverage
test-coverage: test-dependencies
	@mkdir -p build/coverage
	@$(PHPUNIT) --coverage-html build/coverage

.PHONY: test-coveralls
test-coveralls: test-dependencies
	@mkdir -p build/logs
	@$(PHPUNIT) --coverage-clover build/logs/clover.xml

.PHONY: test-cleanup
test-cleanup:
	rm -rf tests/sandbox/*

.PHONY: test-container
test-container:
	@-docker-compose -f ./docker-compose.yml run --rm app bash
	@docker-compose -f ./docker-compose.yml down -v

.PHONY: doc
doc: vendor
	@mkdir -p build/docs
	@apigen generate \
	--source lib \
	--destination build/docs/ \
	--title "$(PACKAGE_NAME) v$(PACKAGE_VERSION)" \
	--template-theme "bootstrap"

.PHONY: clean
clean:
	@rm -fR build
	@rm -fR vendor
	@rm -f composer.lock
