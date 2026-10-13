# SIA Track

SIA Track is a Laravel attendance and registration system with role-based
accounts, academic sections, class schedules, and an optional ACR122U NFC
reader bridge.

## Requirements

- PHP 8.2 or later
- Composer
- Node.js and npm
- MySQL (the current project `.env` uses MySQL) or SQLite
- Python 3 on Windows if the NFC bridge is required
- An installed ACR122U/PCSC smart-card reader for NFC attendance

## First-time setup

Run these commands from the project folder:

```bat
composer install
copy .env.example .env
php artisan key:generate
```

If a `.env` file already exists, do not overwrite it. Verify the database
settings before continuing. For MySQL, set `DB_DATABASE`, `DB_USERNAME`, and
`DB_PASSWORD` in `.env`, and make sure the database already exists. For
SQLite, use `DB_CONNECTION=sqlite` and create the database file:

```bat
type nul > database\database.sqlite
```

Install and build the frontend assets:

```bat
npm install
npm run build
php artisan storage:link
```

## Database setup order

Run the migration first, then run the seeders:

```bat
php artisan migrate
php artisan db:seed
```

`DatabaseSeeder` runs the seeders in this order:

1. `RoleSeeder` - creates ADMIN, FACULTY, STUDENT, and MANAGEMENT roles.
2. `DefaultUsersSeeder` - creates the administrator and management accounts,
   and creates the active `2025-2026`, `1st Semester` academic period when
   needed.
3. `DemoDataSeeder` - creates the demo faculty, sections, students, schedules,
   and student-section assignments.

The demo timetable includes eight subject teachers and eight class periods per
section from Monday through Friday. Periods have varied durations and visible
gaps; STEM A also has overlapping Math and Chemistry classes from 9:00 AM to
11:00 AM and PE from 3:00 PM to 5:00 PM.

To complete an existing demo database without deleting student accounts,
sections, or the existing sample schedules, run:

```bat
php artisan db:seed --class="Database\Seeders\DemoScheduleCompletionSeeder"
```

The completion seeder replaces only a recognized starter timetable (the
original nine demo rows or the previous 120-row demo timetable), adds any
missing demo faculty, and refuses to change unknown schedules.

For a disposable development database only, reset everything and seed it in
one command:

```bat
php artisan migrate:fresh --seed
```

Do not use `migrate:fresh` on a database containing data you need to keep.
Running `DemoDataSeeder` again removes existing faculty and student demo data
before recreating it.

## Seeded accounts

All seeded accounts use the email address as the login username.

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@siatrack.edu.ph` | `AdminPass2026!` |
| Management | `management@siatrack.edu.ph` | `siamanagement@123` |
| Faculty (Juan Dela Cruz) | `juan.delacruz@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Maria Santos) | `maria.santos@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Ramon Magsaysay) | `ramon.magsaysay@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Liza Reyes) | `liza.reyes@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Carlo Mendoza) | `carlo.mendoza@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Ana Villanueva) | `ana.villanueva@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Roberto Garcia) | `roberto.garcia@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Teresa Flores) | `teresa.flores@siatrack.edu.ph` | `siafaculty@123` |

The 30 seeded student accounts all use the password `onesia@123`. Their
emails follow `first-name.last-name@siatrack.edu.ph` (spaces in surnames are
removed):

```text

sophia.bautista@siatrack.edu.ph
gabriel.fernandez@siatrack.edu.ph
angela.aquino@siatrack.edu.ph
ethan.reyes@siatrack.edu.ph
chloe.gonzales@siatrack.edu.ph
joshua.torres@siatrack.edu.ph
samantha.flores@siatrack.edu.ph
liam.mercado@siatrack.edu.ph
hannah.ocampo@siatrack.edu.ph
nathan.delossantos@siatrack.edu.ph
alexander.perez@siatrack.edu.ph
beatrice.santiago@siatrack.edu.ph
daniel.ramos@siatrack.edu.ph
ella.mendoza@siatrack.edu.ph
lucas.castillo@siatrack.edu.ph
mia.villanueva@siatrack.edu.ph
noah.gutierrez@siatrack.edu.ph
olivia.rivera@siatrack.edu.ph
patrick.delacruz@siatrack.edu.ph
rachel.sanjose@siatrack.edu.ph
adrian.cruz@siatrack.edu.ph
alyssa.reyes@siatrack.edu.ph
benjamin.santos@siatrack.edu.ph
danica.bautista@siatrack.edu.ph
elijah.garcia@siatrack.edu.ph
fiona.marquez@siatrack.edu.ph
jacob.evangelista@siatrack.edu.ph
kaitlyn.beltran@siatrack.edu.ph
marcus.ventura@siatrack.edu.ph
nicole.pineda@siatrack.edu.ph
```

These are development/demo credentials. Change or remove them before using
the application outside a local development environment.

## Run the application

To run only the Laravel application:

```bat
php artisan serve
```

Then open <http://127.0.0.1:8000>.

For automatic post-subject absence notifications, keep Laravel's scheduler
running in a second terminal:

```bat
php artisan schedule:work
```

The scheduler checks completed subject schedules every minute. It only sends
notifications when the schedule, roster, and attendance data are usable; a
completely empty attendance scan is treated as incomplete data. Successful or
queued notifications are keyed to the subject/date/student or adviser so they
are not sent again. Failed deliveries are recorded and retried later.

For the NFC workflow, keep the Laravel server and scheduler running, then
start the bridge in another terminal:

```bat
python -u nfc_bridge.py
```

The bridge posts card taps to `http://127.0.0.1:8000/api/nfc/tap`. On Windows,
the `START_SERVER.bat` helper starts the Laravel server, scheduler, and NFC
bridge, then opens the application:

```bat
START_SERVER.bat
```

Run the first-time setup and database commands before using
`START_SERVER.bat`. The reader must be connected and its Windows smart-card
service/driver must be available for NFC taps to work.

## Useful commands

```bat
php artisan route:list
php artisan optimize:clear
php artisan test
npm run dev
```

Use `npm run dev` instead of `npm run build` during frontend development.
