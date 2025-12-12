.PHONY: help build up down restart logs clean backup restore shell-wp shell-db shell-nginx

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build Docker images
	docker-compose build

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

logs: ## Show logs from all services
	docker-compose logs -f

logs-wp: ## Show WordPress logs
	docker-compose logs -f wordpress

logs-nginx: ## Show Nginx logs
	docker-compose logs -f nginx

logs-db: ## Show database logs
	docker-compose logs -f db

clean: ## Remove all containers, volumes, and images
	docker-compose down -v --rmi all

backup: ## Create backup
	./scripts/backup.sh

restore: ## Restore from backup (usage: make restore BACKUP=20240101_120000)
	./scripts/restore.sh $(BACKUP)

shell-wp: ## Open shell in WordPress container
	docker-compose exec wordpress bash

shell-db: ## Open MySQL shell
	docker-compose exec db mysql -u wordpress -p

shell-nginx: ## Open shell in Nginx container
	docker-compose exec nginx sh

status: ## Show status of all containers
	docker-compose ps

health: ## Check health of all services
	@echo "Checking WordPress..."
	@docker-compose exec -T wordpress php -v || echo "WordPress container not running"
	@echo "Checking Database..."
	@docker-compose exec -T db mysqladmin ping -h localhost -u root -p$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) || echo "Database container not running"
	@echo "Checking Redis..."
	@docker-compose exec -T redis redis-cli ping || echo "Redis container not running"

update-wp: ## Update WordPress core, plugins, and themes
	docker-compose exec wordpress wp core update --allow-root
	docker-compose exec wordpress wp plugin update --all --allow-root
	docker-compose exec wordpress wp theme update --all --allow-root

install-wp-cli: ## Install WP-CLI in WordPress container (if not already installed)
	docker-compose exec wordpress curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	docker-compose exec wordpress chmod +x wp-cli.phar
	docker-compose exec wordpress mv wp-cli.phar /usr/local/bin/wp

ssl-init: ## Initialize SSL certificates (usage: make ssl-init DOMAIN=yourdomain.com EMAIL=admin@yourdomain.com)
	./scripts/init-ssl.sh $(DOMAIN) $(EMAIL)

deploy: ## Deploy to AWS Lightsail
	./scripts/deploy-lightsail.sh

setup-cdn: ## Set up CloudFront CDN
	./scripts/setup-cloudfront.sh




