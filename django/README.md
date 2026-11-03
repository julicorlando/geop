# GEOP Django migration

This directory is the first Django implementation slice for GEOP. The PHP application remains in `../geop.shoppingcarpina.com.br` as the working production baseline and rollback copy.

## Included in this slice

- Django 5.2 project configuration for PostgreSQL, environment-based secrets, secure production cookies, HTTPS redirect, and WhiteNoise static files.
- Custom accounts with the GEOP TI, Diretoria, Gestor, Coordenador, and Agente roles.
- Service-order records with the legacy status and priority vocabulary, locations, categories, requesters, managers, agents, due dates, lifecycle timestamps, comments, and soft deletion.
- Login-protected dashboard, order list, order creation, details, status updates, and an audit trail.
- Two-person deletion approval flow: the requester cannot decide their own request, approval does not delete the order, and only TI can execute it.
- Django admin and a container entry point suitable for a later Coolify deployment.

## Run locally

```sh
cd django
python -m venv .venv
. .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env  # set a fresh DJANGO_SECRET_KEY and database URL
python manage.py migrate
python manage.py createsuperuser
python manage.py runserver
```

Create active locations and categories in `/admin/` before creating orders. For production, configure PostgreSQL, a persistent private media volume, `DJANGO_ALLOWED_HOSTS`, `CSRF_TRUSTED_ORIGINS`, and a strong secret key. Do not point Django at the live legacy database for writes.

## Migration boundary

This is a parallel Django foundation, not a cutover release. Existing PHP behavior and live records are untouched. Before replacing PHP, subsequent slices must port and validate attachments and private file delivery, public OS submission, SLA/reopening tokens, preventive schedules, cleaning orders, inventory, procurement/stock, notifications, reports/exports, AI integrations, and the mobile client. Then create a reviewed, repeatable data migration from a production backup, map legacy roles/IDs/statuses, preserve uploaded files and audit history, compare row counts, and run acceptance in staging. Do not switch production until that parity and data reconciliation work is complete.

The legacy project contains real uploaded documents and photographs. They are deliberately not copied into this Django source tree.
