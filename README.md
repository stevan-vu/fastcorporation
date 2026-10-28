# FastCorporation

A small PHP 8.5 web application with registration and login, role-based access, event tracking, and admin statistics and reports. It is built without a framework, on a custom router, [Auryn](https://github.com/maksimovic/auryn) dependency injection, PDO and MySQL.

## Running it

Requirements: Docker with Docker Compose.

```bash
docker compose up -d --build
```

On Linux, if your user id is not 1000 (check with `id -u`), pass your ids so the container can write `vendor/` into the project folder:

```bash
HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose up -d --build
```

Open **http://localhost:8080/login**.

On first start, the PHP container installs Composer dependencies into `vendor/`. MySQL creates the schema and seed data from `database/init.sql`. If the install fails, the PHP container stops instead of serving a broken app; `docker compose logs php` shows why.

| Account | Email | Password | Role |
|---|---|---|---|
| Seeded admin | `admin@example.com` | `secret` | admin |
| Your own | register at `/register` | at least 8 characters | user |

The project folder is mounted into the container, so code changes take effect on the next request without a rebuild.

To reset the database to the seed data:

```bash
docker compose down -v && docker compose up -d
```

## Running the tests

```bash
docker compose exec php composer test
# or, with a readable list of tests:
docker compose exec php vendor/bin/phpunit --testdox
```

The 39 unit tests cover the services, the validator, the permission and CSRF checks. They use in-memory repositories, so they run in under a second and don't need MySQL.

## Features

| Page | URL | Who can open it |
|---|---|---|
| Login, Register | `/login`, `/register` | everyone |
| Page A, with a "Buy a cow" button | `/page-a` | logged-in users |
| Page B, with a "Download" button | `/page-b` | logged-in users |
| Statistics: all events, filterable by date, user and action, 50 per page | `/statistics` | admin |
| Reports: a chart and a table of page A/B views and button clicks per day | `/reports` | admin |
| Logout (POST, from the menu) | `/logout` | logged-in users |

A menu at the top shows only the pages the current user can open.

The app logs these events to the `events` table:
- `login`, `logout`, `registration`
- `view-page-a`, `view-page-b`
- `click-buy-cow`, `click-download`

## Architecture

```
public/index.php           entry point: session, request input, routes
src/
  bootstrap.php            Auryn container: PDO config, interface bindings, shared services
  routes.php               every route and the permission it requires
  Core/Router/             Router (route registration, matching, dispatch)
  Core/Http/               Response (HTML pages, redirects, file downloads)
  Core/View/               minimal PHP template renderer
  Controller/              HTTP only: read input, call services, render a view
  Service/                 application logic (UserService, EventService)
  Security/                Auth (current user, permissions), Csrf (form tokens), AccessGuard (checks before each controller)
  Validator/               input validation rules
  Repository/              interfaces plus their PDO implementations; the only code that runs SQL
  Entity/                  User, Event (with the event name constants), DailyCount (a report row)
  Exception/               RegistrationValidationException
  views/                   templates (layout, menu, pages)
public/css, public/js      styles and Chart.js
storage/                   the file served by the Download button
tests/                     PHPUnit tests and in-memory fake repositories
database/init.sql          schema and seed data
```

### Request flow

```
index.php → Router::dispatch() → AccessGuard::check()
              ├─ unknown path                          → 404
              ├─ POST without a valid CSRF token       → 403
              ├─ route needs a permission, not logged in → redirect to /login
              ├─ logged in but missing the permission  → 403
              └─ Auryn builds the controller and calls the action
                     Controller → Service → RepositoryInterface → PDO
```

### Dependency injection

Constructors declare what they need, and Auryn builds the whole chain automatically: controller, then service, then repository, then PDO. `bootstrap.php` is the only place with configuration:

- **PDO:** connection settings, shared so there's one connection per request.
- **Repositories:** `alias()` binds each repository interface to its PDO implementation. Services depend only on the interfaces, which is what lets the tests replace them with in-memory fakes.
- **Auth and Csrf:** shared, so the router, controllers and views see the same user, cached permissions and form token.

Form and query parameters are passed to controller actions by name. For example, `login(string $email, string $password)` receives the `email` and `password` fields.

### Access control

Permissions come from the database (`roles` → `role_permissions` → `permissions`) and are checked per route in `routes.php`:

```php
$router->get('/login', [AuthController::class, 'loginForm'], Router::PUBLIC);
$router->get('/page-a', [PageController::class, 'pageA']);   // default: 'view_pages'
$router->get('/statistics', [StatisticsController::class, 'index'], 'view_pages_with_stats');
```

Routes are **private by default**. A route without an explicit permission requires `view_pages`, so forgetting the argument can't expose a page. The menu checks the same permissions, so it only shows links the user can open.

| Role | Permissions |
|---|---|
| admin | authentication, view_pages, view_pages_with_stats, manage_users |
| user | authentication, view_pages |

## Design decisions

- **No framework.** The router, view and DI wiring are small and readable on purpose. Auryn is the only runtime dependency.
- **Service layer and repository interfaces.** Controllers never touch SQL. Business rules live in services, and SQL lives only in repositories. The interfaces exist so services can be unit-tested with fakes.
- **Post/Redirect/Get.** A successful login or registration redirects with status 303, so refreshing the next page doesn't submit the form again. Failed submissions show the form again with an error and the values already entered (but never the password).
- **Security basics:**
  - passwords hashed with `password_hash()`
  - a new session id on login, to prevent session fixation
  - `HttpOnly` and `SameSite=Lax` session cookies
  - prepared statements everywhere
  - all output escaped with `htmlspecialchars()`
  - registration always assigns the `user` role on the server
  - CSRF tokens on every POST form (including login and logout), checked centrally in the router
- **Reports.** Every day in the selected range appears in the table and the chart, with zeros on days without events. Dates are filtered as ranges, `created_at >= day AND < next day`, rather than with `DATE(created_at)`, so the index on `created_at` can be used. The chart uses [Chart.js](https://www.chartjs.org/) 4.4.1 (MIT license), served from `public/js/` with no CDN dependency. Series often have equal counts, so their lines overlap; each series therefore has its own marker shape, and the click series are dashed and drawn on top, so no line is hidden.
- **Event names as constants** on `Event` (`Event::LOGIN`, `Event::VIEW_PAGE_A`, ...), shared by the code that logs events and the reports that count them.

## Known limitations and next steps

- `manage_users` is seeded, but there's no user-management page yet.
- The PDO repositories and controllers have no automated tests yet. They were tested manually over HTTP. Integration tests against a test database would be the next step.
- `storage/dummy.exe` is an empty placeholder file.
