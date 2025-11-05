up: ## Starts project containers.
	ddev start

down: ## Stop project containers.
	ddev stop

remove: ## Removes project containers.
	ddev stop
	ddev remove

cr: ## Rebuild Drupal caches.
	ddev drush cr
cc: ## Alias of `make cr`
	make cr

cex: ## Export Drupal configuration.
	ddev drush cex -y

cim: ## Import Drupal configuration.
	ddev drush cim -y

uli: ## Generate a one-time login link.
	ddev drush uli

deploy: ## Run Drush Deploy script
	ddev drush deploy

drupal-site-install: ## Install Drupal from existing site config.
	ddev composer install

install: ## Initialise project containers & install Drupal.
	make up
	make drupal-site-install
	make install-frontend
	make install-storybook

local-settings: ## Add local Drupal development settings.
	cp docroot/sites/example.settings.local.php docroot/sites/default/settings.local.php
	echo "Created \`docroot/sites/default/settings.local.php\` from \`docroot/sites/default/example.settings.local.php\`"

mysql:
	ddev exec mysql -u db -pdb db

install-frontend:
	cd docroot/themes/custom/wateraid && \
	ddev npm i --quiet

watch-frontend:
	ddev npm run dev --prefix docroot/themes/custom/wateraid/

build-frontend:
	ddev npm run build --prefix docroot/themes/custom/wateraid/

install-storybook: ## Sets up local storybook
	cd docroot/themes/custom/wateraid && \
	ddev npm install && \
	ddev npm install watch && \
	ddev npx sb init --builder webpack5 --type server --no-dev
	cp docroot/themes/custom/wateraid/defaults/storybook.main.js docroot/themes/custom/wateraid/.storybook/main.js

storybook: ## Run Storybook. Todo: see why we need to keep enabling Corepack
	cd docroot/themes/custom/wateraid && \
	ddev npm install && \
	ddev npm run storybook -- --no-open
	echo "==================================================="
	echo "Storybook available at https://wateraid.ddev.site:6006/"
	echo "==================================================="

all-stories: ## Generate all storybook stories
	ddev drush storybook:generate-all-stories

force-all-stories: ## Generate all storybook stories (force)
	ddev drush storybook:generate-all-stories --force

watch-all-stories: ## Watch all Storybook stories for changes
	watch --color ddev drush storybook:generate-all-stories

generate-sdc: ## Generate SDC template files
	ddev drush generate single-directory-component
