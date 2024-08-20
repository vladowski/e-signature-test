composer install
cp .env.example .env
php artisan key:generate
php artisan sail:install --with=mysql
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
