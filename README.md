## Limit-Order Exchange Mini Engine "Virgox"

## Link to Virgox Frontend in vuejs

https://github.com/sushantaryal/virgox-vue

## Project Setup

Clone repository

```sh
git clone https://github.com/sushantaryal/virgox virgox-backend

cd virgox-backend

composer install
```

### Environment Variables

Copy `.env.example` to `.env` and update the values.

```
BROADCAST_CONNECTION=pusher
```

and fill these values in the `.env` file

```
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:5173

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

### Database Setup

```sh
php artisan migrate --seed
```

### Running the Application

```sh
php artisan serve
```

## Open new terminal and run

```sh
php artisan queue:work
```

### Login Details

User One

```
email: test@example.com
password: password
```

User Two

```
email: test2@example.com
password: password
```
