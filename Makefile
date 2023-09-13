build-interpreter:
	composer install && php --define phar.readonly=0 compile.php index.php interpreter.phar && mv ./interpreter.phar ./bin/rinha-php-interpreter

build-repl:
	composer install && php --define phar.readonly=0 compile.php repl.php repl.phar && mv ./repl.phar ./bin/rinha-php-repl

run-interpreter: build-interpreter
	./bin/rinha-php-interpreter < source.rinha.json

run-repl: build-repl
	./bin/rinha-php-repl

run-interpreter-with-docker:
	docker-compose up --force-recreate --build

run-repl-with-docker:
	docker-compose -f docker-compose-repl.yml up --force-recreate --build

run-interpreter-with-docker-and-file:
	rinha $(file) > source.rinha.json && docker-compose up --force-recreate --build
