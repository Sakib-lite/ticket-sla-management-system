# Ticket SLA Management System

A support ticket and SLA management system.

- `backend/` — Laravel 13 API
- `frontend/` — Next.js app

## Demo accounts

All seeded users share the password `password`.

| Email | Role |
|---|---|
| admin@supportdesk.test | Administrator |
| agent@supportdesk.test | Support agent |
| sam@supportdesk.test | Support agent |
| priya@supportdesk.test | Support agent |
| customer@supportdesk.test | Customer |
| pat@supportdesk.test | Customer |
| lee@supportdesk.test | Customer |

## Option 1 — Run with Docker

Requires Docker and Docker Compose.

```bash
docker compose up -d --build
```

This starts the database, mail catcher, API, queue worker, scheduler, and frontend, and seeds demo data automatically.

| Service | URL |
|---|---|
| App | http://localhost:3005 |
| API | http://localhost:8005/api |
| Mailpit (catches outgoing email) | http://localhost:8025 |

Run the backend tests:

```bash
docker compose exec backend php artisan test
```

Stop everything:

```bash
docker compose down
```

Add `-v` to also delete the database volume and start fresh next time.

## Option 2 — Run without Docker

Requires PHP 8.4+, Composer, Node 20+, and a MySQL server.

### 1. Database and mail catcher

The easiest way to get both without installing them yourself:

```bash
docker compose up -d mysql mailpit
```

This exposes MySQL on `localhost:3309` and Mailpit on `localhost:8025`. If you'd rather use your own MySQL server, create a database and user matching `backend/.env.example` and skip this step.

### 2. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Run these in three separate terminals:

```bash
php artisan serve --port=8005
php artisan queue:work
php artisan schedule:work
```

The queue worker sends notifications and processes CSV imports in the background. The scheduler checks SLA deadlines every minute. The app works without them, but notifications and SLA escalation won't happen until they're running.

Run the tests:

```bash
php artisan test
```

### 3. Frontend

```bash
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

Open http://localhost:3005.

## Notes

- `FILESYSTEM_DISK=local` and no `storage:link` are needed — attachments are served through an authorized controller, not a public folder.
- Business hours, SLA rules, and ticket number prefix are configured via `backend/.env`, not hardcoded.
- Emails never leave your machine — everything goes to Mailpit.

## Screenshots

<img width="1496" height="704" alt="Screenshot 1" src="https://github.com/user-attachments/assets/50e20b37-2f3d-405f-8793-b5bf887d6391" />

<img width="1512" height="714" alt="Screenshot 2" src="https://github.com/user-attachments/assets/03d24e1a-37bb-4f0c-9e01-928a43e31a1a" />

<img width="1512" height="707" alt="Screenshot 3" src="https://github.com/user-attachments/assets/04e73717-96cf-48b9-aaeb-af9becdc2cc0" />

<img width="1512" height="703" alt="Screenshot 4" src="https://github.com/user-attachments/assets/02222b9c-5167-4c7d-902f-5dde175ef3f0" />
