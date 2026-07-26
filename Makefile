.PHONY: help build up down migrate seed optimize clean logs shell

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Build production image
	docker compose -f docker-compose.prod.yml build

up: ## Start production services
	docker compose -f docker-compose.prod.yml up -d

down: ## Stop production services
	docker compose -f docker-compose.prod.yml down

restart: ## Restart production services
	docker compose -f docker-compose.prod.yml restart app

migrate: ## Run database migrations
	docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

seed: ## Run database seeders
	docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force

optimize: ## Optimize Laravel for production
	docker compose -f docker-compose.prod.yml exec app php artisan optimize

logs: ## Show application logs
	docker compose -f docker-compose.prod.yml logs -f app

shell: ## Access app container shell
	docker compose -f docker-compose.prod.yml exec app sh

clean: ## Remove unused Docker resources
	docker system prune -f

deploy: build up migrate seed optimize ## Full deployment
	@echo "HTTPS: https://tingo.turs.nlsumaranp.dev"
	@echo "phpMyAdmin: http://localhost:8082"
