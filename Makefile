# Campus PSPV — ordres de l'entorn local (Docker). `make help` les llista.
SHELL := /bin/bash
COMPOSE := docker compose
PHP := $(COMPOSE) exec -T web php
PLUGINS := $(shell find plugins -name version.php -not -path '*/tests/*' -exec dirname {} \; 2>/dev/null)

.DEFAULT_GOAL := help
.PHONY: help up down install upgrade cron purge test behat lint backup shell

help: ## Mostra aquesta ajuda
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  make %-9s %s\n", $$1, $$2}'

.env:
	@pass="$$(openssl rand -hex 16)"; admin="Pspv-$$(openssl rand -hex 8)"; \
	sed -e "s|^DB_PASS=$$|DB_PASS=$$pass|" -e "s|^MOODLE_ADMIN_PASS=$$|MOODLE_ADMIN_PASS=$$admin|" .env.example > .env
	@echo "S'ha creat .env amb contrasenyes aleatòries."

moodle/vendor/autoload.php:
	bash bin/moodle-fetch.sh --dev

up: .env moodle/vendor/autoload.php ## Arrenca l'entorn (http://localhost:8080, correu a http://localhost:8025)
	$(COMPOSE) up -d
	@echo "Campus PSPV: http://localhost:$${WEB_PORT:-8080}"

down: ## Atura l'entorn (les dades es conserven)
	$(COMPOSE) down

install: up ## Instal·la Moodle a la base de dades, els idiomes i la memòria cau Redis
	@set -a; source .env; set +a; \
	$(COMPOSE) exec -T web bash -c 'chown www-data:www-data /var/www/moodledata /var/www/phpunitdata /var/www/behatdata'; \
	if $(COMPOSE) exec -T web test -f /var/www/moodledata/.installed; then \
		echo "Moodle ja està instal·lat; s'apliquen les actualitzacions pendents."; \
		$(COMPOSE) exec -T -u www-data web php admin/cli/upgrade.php --non-interactive; \
	else \
		$(COMPOSE) exec -T -u www-data web php admin/cli/install_database.php --lang=ca --agree-license \
			--fullname="Campus PSPV" --shortname="Campus PSPV" --summary="" \
			--adminuser="$$MOODLE_ADMIN_USER" --adminpass="$$MOODLE_ADMIN_PASS" --adminemail="$$MOODLE_ADMIN_EMAIL" && \
		$(COMPOSE) exec -T -u www-data web touch /var/www/moodledata/.installed; \
	fi
	$(COMPOSE) exec -T -u www-data web php public/local/pspvcore/cli/setup.php
	$(COMPOSE) exec -T -u www-data web php public/local/pspvaccess/cli/setup.php
	$(COMPOSE) exec -T -u www-data web php public/theme/pspv/cli/setup.php
	@echo "Fet. Usuari administrador i contrasenya: MOODLE_ADMIN_USER / MOODLE_ADMIN_PASS a .env"

upgrade: ## Aplica les actualitzacions pendents de Moodle i dels plugins
	$(COMPOSE) exec -T -u www-data web php admin/cli/upgrade.php --non-interactive
	$(COMPOSE) exec -T -u www-data web php admin/cli/purge_caches.php

cron: ## Executa el cron de Moodle una vegada
	$(COMPOSE) exec -T -u www-data web php admin/cli/cron.php

purge: ## Buida les memòries cau
	$(COMPOSE) exec -T -u www-data web php admin/cli/purge_caches.php

# Els tests s'executen com a root dins el contenidor: han d'escriure phpunit.xml i behat.yml al core.
test: ## PHPUnit dels plugins propis (make test FILTER=nom_del_test)
	$(COMPOSE) exec -T web php public/admin/tool/phpunit/cli/init.php --disable-composer
	@for p in $(PLUGINS); do \
		c="$$(grep -oE "component\s*=\s*'[a-z_0-9]+'" $$p/version.php | grep -oE "'[a-z_0-9]+'" | tr -d "'")"; \
		echo "== $$c"; \
		$(COMPOSE) exec -T web vendor/bin/phpunit --testsuite "$${c}_testsuite" $(if $(FILTER),--filter $(FILTER),) || exit 1; \
	done

behat: ## Behat dels plugins propis (make behat TAGS=@local_pspvcore)
	$(COMPOSE) --profile behat up -d selenium
	$(COMPOSE) exec -T web php public/admin/tool/behat/cli/init.php --disable-composer
	$(COMPOSE) exec -T web php public/admin/tool/behat/cli/run.php --tags="$(or $(TAGS),@pspv)"

lint: ## Sintaxi PHP i estil de codi Moodle (phpcs) dels plugins propis
	@for p in $(PLUGINS); do find $$p -name '*.php' -print0 | xargs -0 -n1 php -l > /dev/null || exit 1; done; echo "Sintaxi PHP correcta."
	@if [ -x vendor/bin/phpcs ]; then vendor/bin/phpcs --standard=moodle --extensions=php $(PLUGINS) && echo "Estil Moodle correcte."; \
	else echo "phpcs no instal·lat: executa \`composer install\` a l'arrel del repositori (la CI ho comprova igualment)."; fi

backup: ## Còpia de seguretat de la base de dades i de moodledata a backups/
	@mkdir -p backups; set -a; source .env; set +a; ts=$$(date +%Y%m%d-%H%M%S); \
	$(COMPOSE) exec -T db mariadb-dump --single-transaction -u"$$DB_USER" -p"$$DB_PASS" "$$DB_NAME" | gzip > backups/db-$$ts.sql.gz && \
	$(COMPOSE) exec -T web tar -C /var/www -czf - --exclude='moodledata/cache' --exclude='moodledata/localcache' \
		--exclude='moodledata/sessions' --exclude='moodledata/temp' --exclude='moodledata/trashdir' moodledata > backups/moodledata-$$ts.tar.gz && \
	echo "Còpia a backups/*-$$ts.*"

shell: ## Obre una consola al contenidor web
	$(COMPOSE) exec -u www-data web bash
