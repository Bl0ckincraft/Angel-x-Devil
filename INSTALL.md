# How to install project

## Use git

```bash
# First time install project
cd pathToProjectParentFolder
git clone https://github.com/Bl0ckincraft/Angel-x-Devil.git
cd Angel-x-Devil
# To update project
git fetch
git stash
git merge
```

## Stripe setup

You also need to create a stripe account in order to accept payment with this website. <br/>
<br/>
You will need to add some webhooks to your stripe :

- A webhook which listen 'checkout.session.completed' event and redirect to 'http://your_website/stripe/session'. <br/>
  it will be used to confirm that an order was paid when stripe will confirm payment.

## Create .env file

Create a new `.env` file and **complete it with your values**.
> :warning: Make sure to keep .env file private and don't commit it.

```dotenv
APP_ENV="dev"
APP_SECRET=""

# Database could be generate user, in this case try to check if pdo-mysql php extension was enabled
# (remove the ';' behind 'extension=pdo_mysql' in php.ini file)
# If your database password contains special chars, use url_encode() function and replace % by %%
DATABASE_URL="mysql://user:password@ip:port/dbname?serverVersion=8&charset=utf8mb4"

MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0"

STRIPE_SECRET_KEY="" # Your stripe secret key
STRIPE_PUBLIC_KEY="" # Your stripe public key
STRIPE_SESSION_WEBHOOK_SECRET_KEY="" # Webhooks are in same order than upper
DOMAIN="" # Like 'https://my_domain:my_port'
```

## Install packages

```bash
# Then install default project packages
composer install
```

## Init database

```bash
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
```

## Deploy webapp

```bash
composer dump-env prod
APP_ENV=PROD APP_DEBUG=0 php/bin console cache:clear
```

## Notes

You should need to activate some php extensions like :
```ini
extension=fileinfo
extension=imap
extension=openssl
extension=pdo_mysql
```