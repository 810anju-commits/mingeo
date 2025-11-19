# Mining & Geology Prototype (Core PHP)

This repository contains a Composer-free PHP prototype for the Mining & Geology Department. It implements the three major personas described in the specification:

1. **Public portal** – dashboard cards, file search, status flow reference.
2. **District office portal** – dashboard, monthly trend chart, application entry form with all mandated fields, pending/ disposed/ sanctioned views, and file search.
3. **Directorate (DMG) portal** – state level dashboard, district comparison, monthly trend, per-district drill down, and application processing screen.

All pages are built with vanilla PHP, mysqli, and lightweight CSS so the system can run on any shared hosting stack.

## Project structure

```
config/            Database settings
includes/          Repository + helper utilities
assets/css/        Global styles
public/            Public portal landing page
district/          District office portal
DMG/               Directorate dashboard + detail views
Data/              Sample dataset + schema helpers
```

## Database

The app tries to connect to MySQL using `config/database.php`. When a connection is not available it falls back to the sample dataset in `data/sample_data.php`, so you can explore the UI immediately.

To provision MySQL locally run the SQL in `data/schema.sql` (create this database and table first) and update the credentials in `config/database.php`.

## Running locally

```bash
php -S 0.0.0.0:8080
```

Now open:

- `http://localhost:8080/public/` – public portal
- `http://localhost:8080/district/` – district dashboard
- `http://localhost:8080/dmg/` – DMG dashboard

## Saving data

The district Application Entry form posts directly to the repository. When MySQL is unavailable, submitted payloads are stored in `data/runtime/applications.json` so you can review the request later.

## Status flow

The prototype mirrors the process defined in the brief:

```
Received → Under Processing (District) → Forwarded to DMG
If clarification needed → Returned to District → Resubmission → DMG
Final outcome → Approved / Rejected / Disposed
Special case (QP) → District Processing → Sanctioned → Disposed
```

This ensures the same terminology is rendered on both the district and DMG portals.
