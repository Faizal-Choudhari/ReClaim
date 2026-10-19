# College Lost & Found Portal

A PHP 8.x + MySQL college portal for reporting lost/found items, uploading photos, searching reports, submitting claims, and tracking them. Includes student USN/contact details and dashboards. No XAMPP is required.

## Included features
- Student registration with unique USN, contact number, email, and password.
- Login/logout with password hashing, session regeneration, and CSRF protection.
- Lost/found reports with JPG/PNG/WEBP/GIF photo uploads (2 MB maximum per uploaded image).
- Four sample reports with bundled photos.
- Search and filter by keyword, item type, category, and location.
- Claim requests and claim status tracking.
- Student dashboard showing USN, contact number, email, own reports, and claims.
- Item detail page shows the reporter's USN and contact number so students can coordinate.
- Admin dashboard for users, reports, and claims.
- Responsive green-themed interface.

## Windows local setup (PHP installed directly, no XAMPP)

1. Extract the ZIP. Keep the `Mini` folder together.
2. Ensure MySQL is running and PHP has `pdo_mysql`, `mbstring`, and `fileinfo` enabled in `C:\php\php.ini`.
3. Configure the database:
   - **Fresh install:** Open `sql/schema.sql` in MySQL Workbench and execute it. WARNING: this script drops/recreates the `users`, `items`, and `claims` tables in `college_lost_found`.
   - **Existing database with data to preserve:** Back up your database first, then execute `sql/migrate_usn_phone.sql` in MySQL Workbench. This adds USN/contact fields without dropping your current reports. Run this migration only once.
4. Copy `.env.example` to a file named `.env` in the `Mini` folder. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` to match your local MySQL account. The ZIP intentionally does not contain a real `.env` or database password.
5. Ensure the `uploads` folder is writable by the PHP process. The four sample photos are included in this folder already.
6. Double-click `start-server.bat`, or run in VS Code PowerShell:
   ```powershell
   & "C:\php\php.exe" -c "C:\php\php.ini" -d upload_max_filesize=4M -d post_max_size=6M -S localhost:8000 -t "C:\Users\FAIJAL\Desktop\Mini"
   ```
   If you extract to another folder, change the path in that command or use `start-server.bat`.
7. Open http://localhost:8000. Database diagnostic: http://localhost:8000/test-db.php.

## Demo accounts (fresh install only)
| Role | Email | Password | USN | Contact |
|---|---|---|---|---|
| Admin | admin@college.edu | Admin@123 | ADMIN0001 | 9000000001 |
| Student | asha@college.edu | Student@123 | 1BM24CS001 | 9000000002 |
| Student | ravi@college.edu | Student@123 | 1BM24CS002 | 9000000003 |

Change demo passwords before using this project beyond local testing. New registrations are students; an admin can manage roles from Admin → Users.

## Photos
- Sample photos are in `uploads/wallet.jpg`, `uploads/bottle.jpg`, `uploads/calculator.jpg`, and `uploads/keys.jpg`.
- Newly submitted photos are validated by MIME type and image decoding, limited to 2 MB, renamed to random filenames, and stored in `uploads/`.
- The database stores only the filename in `items.photo`.

## Deployment notes
This is a PHP/MySQL project, not a static-only site. Deploy it to a host that supports PHP 8.x and MySQL, upload all project files, configure environment credentials in a private `.env`, import `sql/schema.sql` on a fresh database (or the migration on an existing database), enable `pdo_mysql`, `mbstring`, and `fileinfo`, and ensure `uploads/` is writable. Use HTTPS, change demo passwords, and do not publish `.env`. Do not use PHP's built-in development server for production hosting.

## Troubleshooting
- **Database connection failed:** verify `.env`, MySQL service, database name, username, and password.
- **could not find driver:** enable `extension=pdo_mysql` in `php.ini`.
- **Call to undefined function `mb_strlen()`:** enable `extension=mbstring` and restart PHP.
- **Photo upload failed:** enable `extension=fileinfo`; check that `uploads/` is writable and the image is at most 2 MB.
- **Photos not shown:** verify the matching filename exists in `uploads/` and the `items.photo` column contains that filename.

## Main files
`index.php` browse cards · `register.php` registration · `report.php` lost/found form · `item.php` item details/claims/contact · `dashboard.php` student dashboard · `admin.php` admin panel · `functions.php` helpers/uploads · `db.php` database connection · `sql/schema.sql` fresh database setup · `sql/migrate_usn_phone.sql` migration for existing data.
