# ReClaim – College Lost & Found Portal

ReClaim is a PHP 8.4 application using **SQLite**. This edition is packaged for a simple, single-service Render deployment: the PHP website creates its SQLite database automatically on first use. A separate MySQL service is not needed.

## Features

- Student registration and login
- Admin dashboard for users, reports and claims
- Report lost and found items with photos
- Search and filter item reports
- Submit and manage claims
- Edit or delete your own reports
- CSRF protection, password hashing and validated image uploads
- Responsive interface

## Deploy on Render (one Web Service)

1. Push this project to a GitHub repository. The `Dockerfile` must be in the repository root.
2. In Render, choose **New + → Web Service** and connect the repository.
3. Select **Docker** as the runtime. Keep the Dockerfile path as `./Dockerfile` and build context as `.`.
4. Before using the site with real users, add a persistent disk to this Web Service:
   - Mount path: `/var/data`
   - Size: choose enough space for your database and uploaded photos.
5. Deploy. Open the website URL. The SQLite database and tables are created automatically on the first request.

**Why the disk matters:** Render's normal container filesystem is temporary. Without a persistent disk, the SQLite database, registered users, reports, claims and uploaded photos can be lost when Render replaces the instance. Persistent disks may require a paid Render plan. The website is one service, but durable data still needs persistent storage.

## First-run demo accounts

The first run creates sample accounts and reports only when the SQLite database is empty:

- Admin: `admin@college.edu`
- Student: `asha@college.edu`
- Student: `ravi@college.edu`

The sample password hashes are retained from the project's original schema. If the sample passwords do not work, create a new student account and promote it to admin only after verifying the database setup. Change/remove demo accounts before public use.

## Local Windows setup

1. Use PHP 8.4 with `pdo_sqlite`, `mbstring` and `fileinfo` enabled.
2. Open PowerShell in the project folder.
3. Run:

```powershell
php -S localhost:8000
```

4. Open `http://localhost:8000`. The database is created at `data/reclaim.sqlite` automatically.

## Important data migration note

This SQLite edition starts with a **new SQLite database**. It does not automatically copy existing users, reports, claims or uploaded photos from the old MySQL/Railway database. Keep the old deployment and database unchanged until any required data has been exported and migrated. Do not delete the old database as part of this deployment.

## Project structure

```text
ReClaim/
├── assets/             # CSS, JavaScript and sample photos
├── uploads/            # bundled sample photos; runtime uploads use persistent storage
├── db.php              # SQLite connection and automatic schema setup
├── Dockerfile          # PHP 8.4 + SQLite extensions
├── docker-entrypoint.sh
├── admin.php
├── dashboard.php
├── report.php
└── ...
```
