.PHONY: test
test:
	tools/phpstan --level=7 --autoload-file=vufind-autoload.php analyze src
