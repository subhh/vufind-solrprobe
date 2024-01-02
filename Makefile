.PHONY: test
test: test.phan test.phpstan test.phpunit

.PHONY: test.phan
test.phan:
	PHAN_DISABLE_XDEBUG_WARN=1 tools/phan --no-progress-bar

.PHONY: test.phpstan
test.phpstan:
	tools/phpstan --no-progress --level=7 --autoload-file=vufind-autoload.php analyze src/main

.PHONY: test.phpunit
test.phpunit:
	tools/phpunit --bootstrap vendor/autoload.php --verbose src
