# install
clone the repository
composer update
php bin/console doctrine:migrations:migrate
php bin/console make:migration

# configure

nano .env
Set DATABASE_URL and MAILER_URL

Open browser and create a user, then make them admin, with their ID
ADMIN_ID=1

# move to production
APP_ENV=prod

