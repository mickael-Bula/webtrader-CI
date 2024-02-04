# Makefile for Symfony Project on Windows

#---SYMFONY--#
sf = symfony
SYMFONY_CONSOLE = $(sf) console

# Composer 🎵
install:
	$(sf) composer install

update:
	$(sf) composer update

# Symfony 🎼
serve:
	$(sf) serve -d

stop:
	$(sf) server:stop

open:
	$(sf) open:local

start:
	$(sf) serve -d && $(sf) open:local

# Tests 🎯
test:
	$(sf) php bin/phpunit

# Database
migrate:
	$(sf) doctrine:migrations:migrate

# Cleaning
clean:
	del /Q var\cache\* var\log\*

# Affiche les variables d'environnement utilisées par Symfony
dump-env:
	$(SYMFONY_CONSOLE) debug:dotenv

# Help 🆘
help:
	@echo "Liste des commandes disponibles :"
	@echo "  install          - Installe les dépendances du projet"
	@echo "  update           - Mise à jour des dépendances du projet"
	@echo "  serve            - Lance le serveur de développement de Symfony"
	@echo "  stop             - Arrête le serveur de développement de Symfony"
	@echo "  start            - Lance le serveur de développement de Symfony et ouvre le projet dans le navigateur"
	@echo "  test             - Lance les tests PhpUnit"
	@echo "  migrate          - Lance les migrations de la base de données"
	@echo "  clean            - Nettoie le cache et les fichiers de logs"

first-install-tdd:
	composer require symfony/orm-pack security ; \
	composer require --dev \
		codeception/codeception \
		codeception/module-asserts \
		codeception/module-doctrine2 \
		codeception/module-phpbrowser \
		codeception/module-rest \
		codeception/module-symfony \
		fakerphp/faker \
        maker ; \
    cp .env .env.local ; \
    cp .env .env.test.local ;

