# MediBook — Doctor Appointment System

A direct patient↔doctor booking platform. No receptionist, no middleman: patients find a  
doctor, pick a free slot, and the doctor confirms or rejects it themselves.

Built with **PHP 8 (procedural, PDO)**, **MySQL/MariaDB**, **Bootstrap 5** and vanilla JS.  
Designed to run on XAMPP out of the box.

---

## 1. Requirements

| Component | Version                                          |
| --------- | ------------------------------------------------ |
| PHP       | 8.0+ (tested on 8.2.12)                          |
| MySQL     | 5.7+ / MariaDB 10.4+ (tested on MariaDB 10.4.32) |
| Apache    | XAMPP 8.x                                        |
| Browser   | Any modern browser                               |

No Composer, no build step, no external PHP libraries.

---

## 2. Setup on XAMPP

### Step 1 — Put the files in place

Copy the whole project folder into XAMPP's web root so it becomes `saan`:

```
C:\xampp\htdocs\saan\
```

The app auto-detects its own URL, so it works from `http://localhost/saan/` **or** from a  
virtual-host root without any config change.

> **Working from another drive?** Instead of copying, link the folder once (no admin rights
> needed, and edits stay in one place):
>
> ```powershell
> New-Item -ItemType Junction -Path "C:\xampp\htdocs\saan" -Target "I:\saan"
> ```
>
> URL detection follows the request path rather than `DOCUMENT_ROOT`, so it survives junctions
> and symlinks. Remove the link later with `Remove-Item C:\xampp\htdocs\saan` (this removes the
> junction only, never your files).

### Step 2 — Start the servers

Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.

### Step 3 — Import the database

1. Go to <http://localhost/phpmyadmin>
2. Click the **Import** tab (do *not* create a database first — the script creates it)
3. Choose **`database.sql`** from the project root → **Import**

Or from the command line:

```bash
cd C:\xampp\mysql\bin
mysql -u root -p < C:\xampp\htdocs\saan\database.sql
```

This creates the `doctor_appointment` database, all tables, and sample data  
(7 users, 4 doctors, 11 availability windows, 5 appointments, 2 reviews).

> Re-running `database.sql` **drops and recreates** the database — handy for resetting the demo.

### Step 4 — Configure

Open **`config/config.php`** and check the top block:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'doctor_appointment');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default: empty password
```

Also worth a look:

| Constant               | Default      | Meaning                                               |
| ---------------------- | ------------ | ----------------------------------------------------- |
| `APP_TIMEZONE`         | `Asia/Dhaka` | Drives "today", slot cutoffs and all date maths       |
| `CURRENCY_SYMBOL`      | `$`          | Consultation fee display                              |
| `SLOT_LEAD_MINUTES`    | `60`         | A slot must start at least this many minutes from now |
| `BOOKING_HORIZON_DAYS` | `30`         | How far ahead patients may book                       |
| `MAIL_ENABLED`         | `false`      | See §7                                                |

### Step 5 — Open the app

<http://localhost/saan/>

### Step 5b — Verify the connection

Open <http://localhost/saan/install.php>. It checks the PHP build, the `pdo_mysql` driver,
your credentials, the schema, the seed rows, URL detection and folder permissions — and if the
tables are missing it imports `database.sql` for you with one click.

Green across the board means the database is connected. A red **Database connection** row shows
the exact driver error plus what to try.

> `install.php` only responds to `127.0.0.1` / `::1`. Delete it before deploying publicly.

> **Already running?** If you want to look around without starting Apache, PHP's built-in
> server works too:
>
> ```bash
> cd C:\xampp\htdocs\saan
> C:\xampp\php\php.exe -S 127.0.0.1:8080 -t .
> ```
>
> Then open <http://127.0.0.1:8080/>. Stop it with `Ctrl+C`. Apache is still the better choice
> for normal use — the `.htaccess` protection on `config/`, `includes/` and `storage/` only
> applies under Apache, not under `php -S`.

### Step 6 — Make the uploads folder writable (Windows only)

Doctor photos are written to `assets/uploads/doctors/`. That folder ships with the project and  
XAMPP runs as your user on Windows, so normally nothing to do. If photo upload fails, check the  
folder exists and is not read-only.

---

## 3. Default logins

All sample accounts use the passwords below. **Change them before any real use.**

| Role                           | Email                   | Password      |
| ------------------------------ | ----------------------- | ------------- |
| Admin                          | `admin@medibook.test`   | `Admin@123`   |
| Patient                        | `patient@medibook.test` | `Patient@123` |
| Patient                        | `emily@medibook.test`   | `Patient@123` |
| Doctor (verified, Cardiology)  | `sarah@medibook.test`   | `Doctor@123`  |
| Doctor (verified, Dermatology) | `david@medibook.test`   | `Doctor@123`  |
| Doctor (verified, Pediatrics)  | `aisha@medibook.test`   | `Doctor@123`  |
| Doctor (pending verification)  | `mark@medibook.test`    | `Doctor@123`  |

`mark@medibook.test` is deliberately left **pending** so you can walk through the admin  
verification flow: sign in as admin → Doctors → Verify.

---

## 4. Folder structure

```
saan/
├── index.php                  Landing page: doctor search + specialty browse
├── install.php                Setup health check (localhost only — delete on production)
├── database.sql               Schema + sample data (drop-and-recreate)
├── README.md
│
├── config/
│   ├── config.php             App constants, timezone, session, bootstrap
│   ├── database.php           PDO factory (single shared connection)
│   └── .htaccess              Blocks direct web access
│
├── includes/
│   ├── functions.php          Escaping, CSRF, flash, role guards, slot maths, notifications
│   ├── header.php             Shared <head>, navbar, flash rendering
│   ├── footer.php             Shared scripts / closing markup
│   └── .htaccess              Blocks direct web access
│
├── auth/
│   ├── login.php              One form, redirects by role
│   ├── register.php           Patient or doctor sign-up
│   └── logout.php             POST-only, CSRF-protected
│
├── patient/
│   ├── dashboard.php          Next appointment, quick stats, recent activity
│   ├── doctors.php            Search by name / specialty / location
│   ├── doctor.php             Profile, fee, reviews, slot picker
│   ├── book.php               Create a booking request
│   ├── appointments.php       History + cancel / reschedule
│   ├── prescriptions.php      Visit notes and prescriptions
│   ├── review.php             Rate a completed visit
│   ├── complaints.php         File and track complaints
│   └── profile.php            Patient details
│
├── doctor/
│   ├── dashboard.php          Today's schedule, pending requests
│   ├── appointments.php       Accept / reject / complete, add notes
│   ├── calendar.php           Month view of bookings
│   ├── patients.php           Patient list with visit history
│   ├── availability.php       Weekly template editor
│   └── profile.php            Specialty, fee, experience, bio, photo
│
├── admin/
│   ├── dashboard.php          Platform stats at a glance
│   ├── doctors.php            Verify / reject doctor accounts
│   ├── users.php              Block / unblock / delete users
│   ├── reports.php            Appointments, active doctors, top-rated
│   └── complaints.php         Complaint queue
│
├── assets/
│   ├── css/style.css          Theme, cards, slot grid, dashboards
│   ├── js/main.js             Slot picker, confirm dialogs, small UX helpers
│   └── uploads/doctors/       Doctor profile photos
│
└── storage/
    ├── mail.log               Outbound mail stub (created on first message)
    └── .htaccess              Blocks direct web access
```

---

## 5. How the booking flow works

```
Patient searches specialty
        ↓
Views doctor profile + free slots
        ↓
Picks a slot → POST /patient/book.php
        ↓
appointments row created, status = 'pending'   ← slot is now held
        ↓
Doctor sees it in "Requests" → Accept or Reject
        ↓
status = 'confirmed'  →  patient gets in-app notification (+ email if enabled)
        ↓
Visit happens → doctor marks 'completed' and writes notes + prescription
        ↓
Patient reads notes, then leaves a rating + review (once)
```

**Statuses:** `pending` → `confirmed` | `rejected`; `pending`/`confirmed` → `cancelled`;  
`confirmed` → `completed`.

Cancelling or rejecting frees the slot for someone else. Rescheduling is implemented as  
"cancel the old row, create a new pending row" inside one transaction, so the old slot is  
released only if the new one is successfully held.

---

## 6. Database notes

### Double-booking prevention

The schema enforces `UNIQUE (doctor_id, date, time)` — but a naive version of that constraint  
would permanently burn a slot once it was cancelled. So `appointments` carries a **stored  
generated column**:

```sql
slot_lock TINYINT GENERATED ALWAYS AS (
    CASE WHEN status IN ('pending','confirmed','completed') THEN 1 ELSE NULL END
) STORED,
UNIQUE KEY uq_appointment_slot (doctor_id, `date`, `time`, slot_lock)
```

`slot_lock` is `1` while the appointment is live and `NULL` once it is cancelled or rejected.  
Because MySQL treats `NULL`s as distinct inside a UNIQUE index:

- two **live** appointments can never share the same doctor + date + time (verified: duplicate  
  insert fails with `ERROR 1062`)
- a cancelled/rejected row no longer blocks its slot

The app *also* re-validates every booking server-side (`slot_is_bookable()`), so a tampered  
form cannot book a slot that does not exist in the doctor's availability. The database  
constraint is the final backstop against two simultaneous requests.

### Column naming

`appointments` uses `` `date` `` and `` `time` `` as specified. Both are non-reserved keywords in  
MySQL/MariaDB, but they are backticked everywhere in SQL for clarity.

### Tables

`users`, `doctors`, `patients`, `availability`, `appointments`, `reviews`, `complaints`,  
plus `notifications` (backs the in-app bell).

---

## 7. Email

XAMPP has no working SMTP relay, so `MAIL_ENABLED` is `false` by default. In that mode every  
notification is still written to the `notifications` table **and** appended to  
`storage/mail.log` — enough to prove the flow without a mail server:

```
[2026-10-20 16:40:12] To: patient@medibook.test | MediBook notification | Your appointment with ...
```

To send real mail, set `MAIL_ENABLED` to `true` and configure `sendmail_path` / SMTP in  
`php.ini`. In-app notifications work regardless.

---

## 8. Security

| Concern            | Implementation                                                                                                    |
| ------------------ | ----------------------------------------------------------------------------------------------------------------- |
| Password storage   | `password_hash()` (bcrypt), verified with `password_verify()`                                                     |
| SQL injection      | PDO prepared statements everywhere, `ATTR_EMULATE_PREPARES = false`                                               |
| XSS                | All output escaped through `e()` (`htmlspecialchars`, `ENT_QUOTES`)                                               |
| CSRF               | Per-session token, `hash_equals()` check on every POST                                                            |
| Access control     | `require_login('role')` guard at the top of every protected page; blocked users are ejected on their next request |
| Session            | `HttpOnly`, `SameSite=Lax`, custom session name, regenerated on login                                             |
| File uploads       | Extension + MIME + size checks, random filename, stored outside the web-executable path pattern                   |
| Enumeration        | Login errors never reveal whether an email exists                                                                 |
| Direct file access | `config/`, `includes/`, `storage/` denied via `.htaccess`                                                         |

---

## 9. Build progress

- [x] **Step 1** — Database schema + sample data, config, shared helpers, README
- [x] **Step 2** — Authentication (register / login / logout, role redirects) + base layout & CSS
- [x] **Step 3** — Patient dashboard + doctor search & booking
- [x] **Step 4** — Doctor dashboard (availability, requests, notes, prescriptions)
- [x] **Step 5** — Admin dashboard (verification, users, reports, complaints)
- [x] **Step 6** — Reviews, complaints, notifications, final polish & smoke test

---

## 10. A five-minute tour

Sign in as each role (the login screen has one-click demo buttons) and follow this path:

**As a patient** — `patient@medibook.test` / `Patient@123`

1. Dashboard shows the next appointment and any visit still waiting for a review.
2. *Find a doctor* → filter by specialty or location.
3. Open Dr. Sarah Mitchell → pick a day chip, pick a free slot (taken slots are struck through),
   add a reason, request the booking.
4. *My appointments* → reschedule or cancel it. Cancelling releases the slot immediately.

**As a doctor** — `sarah@medibook.test` / `Doctor@123`

5. The sidebar badge counts requests. Accept one from the dashboard.
6. *Appointments* → *Upcoming* → **Complete visit**, write notes + prescription.
   The patient is notified the moment you save.
7. *Availability* → add or remove weekly windows; the patient-facing slot grid changes instantly.

**As an admin** — `admin@medibook.test` / `Admin@123`

8. `mark@medibook.test` starts **pending**. *Doctors* → **Verify** → he immediately appears in
   public search. *Reject* blocks the account instead.
9. *Users* → block someone and watch their session die on their next click.
10. *Reports* → volume, completion rate, revenue, top-rated and busiest doctors.
11. *Complaints* → reply to Emily's complaint; she gets an in-app notification.

**Reviews** close the loop: after a visit is marked completed, the patient can rate it once
(a `UNIQUE` key on `appointment_id` makes a second review impossible).

---

## 11. Troubleshooting

Start with <http://localhost/saan/install.php> — it names the failing link in the chain.

| Symptom | Cause | Fix |
|---|---|---|
| “Database connection failed” | MySQL not running | Start MySQL in the XAMPP Control Panel |
| Same, with `Unknown database` | Schema never imported | `install.php` → **Import database.sql** |
| Same, with `Access denied` | Wrong credentials | Set `DB_USER` / `DB_PASS` in `config/config.php` |
| Page loads but unstyled | `BASE_URL` wrong | Should be `/saan` at `localhost/saan`. Override it manually by defining `BASE_URL` before the auto-detection block |
| Everything 404s under `/saan` | Project not in `htdocs` | Copy it, or create the junction from §2 Step 1 |
| “Session expired” (419) | Stale form after a session reset | Reload the page and resubmit |
| Photo upload fails | Folder permissions | Ensure `assets/uploads/doctors/` exists and is writable |
| Doctor invisible to patients | Not verified yet | Admin → Doctors → **Verify** |

---

## 12. Known limits

- Email is stubbed to `storage/mail.log` until you configure SMTP (see §7). In-app
  notifications are real either way.
- Rescheduling is implemented as *cancel + rebook in one transaction*, so the appointment gets a
  new id. That keeps the unique-slot guarantee simple; the audit trail is the old cancelled row.
- Slots are generated on read from `availability`, never stored. Changing a doctor's hours does
  not retroactively cancel existing bookings — by design.
- No payment gateway. Fees are displayed and reported, but the consultation is settled offline.
