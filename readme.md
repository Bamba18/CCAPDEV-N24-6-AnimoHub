# AnimoHub

AnimoHub is a web application for the De La Salle University community. It brings course enlistment and student organizations into one place. Students can browse course offerings, manage their class schedules, discover organizations, and apply for membership. Organization officers can post events and announcements, while presidents can review membership requests. Administrators manage accounts and organizations.

This repository contains our CCAPDEV Group 6 machine project. Phase 3 builds on the Phase 2 application by adding password hashing, role permissions, form validation, and other security features.

**Live website:** [AnimoHub on CCS Cloud](http://ccscloud1.dlsu.edu.ph:60101/CCAPDEV-N24-6-AnimoHub-Phase3/index.php?page=login)

## Main features

- Browse and search course offerings. Students can enlist in or drop sections and view their schedules.
- Browse student organizations, see upcoming events, and read announcements.
- Apply to join an organization or request to leave one.
- Manage organization information, events, and announcements with the appropriate role.
- Review membership requests and manage member roles as an organization president or administrator.
- Create administrator and professor accounts from the admin page. Public signup creates student accounts.

## Run locally

1. Download the project and open the folder containing `index.php`.
2. Start MariaDB or MySQL. Import `database/CCAPDEV-N24-6-AnimoHub.sql` using your database tool. **Use the seed on a fresh development instance because it drops and recreates the `animohub` database.**
3. Open `config/db.php` and set the database host, name, username, and password for your setup. The seed uses the database name `animohub`.
4. From the project folder, start PHP’s development server:

   ```bash
   php -S 127.0.0.1:8000
   ```

5. Open [AnimoHub locally](http://127.0.0.1:8000/index.php?page=login). Use a sample account from the submission or register a student account.

### Upgrade an existing database

Back up the existing database and apply the Phase 3 schema additions for `auth_log`, `failed_attempts`, and `locked_until` if they are missing. If existing accounts still have plaintext passwords, run the following command from the project folder:

```bash
php database/hash-passwords.php
```

The script uses the database configured in `config/db.php`. It converts old passwords to bcrypt hashes and skips accounts that are already hashed. Users keep their original passwords.

## Tests

Run the PHP validation tests from the project folder:

```bash
php tests/ValidatorTest.php
```

The saved output is in `tests/test-results.txt`. The prepared statement test is in `tests/InjectionTest.php` and uses PHPUnit with an in-memory SQLite database.

## Dependencies and libraries

- Apache web server for the CCS Cloud deployment
- PHP 8 and MariaDB/MySQL
- PHP extensions `PDO`, `pdo_mysql`, and `mbstring`
- HTML, CSS, and vanilla JavaScript
- PHPUnit and `pdo_sqlite` for the prepared statement test

## Security features

- **Password hashing:** PHP stores bcrypt hashes and uses `password_verify()` to check passwords at login.
- **Password rules and confirmation:** New passwords require at least eight characters, uppercase and lowercase letters, a number, and a special character. Signup checks that both password fields match.
- **Least privilege:** Server-side checks restrict pages and actions based on the user’s account role and permissions in the selected organization.
- **Form validation:** JavaScript gives users immediate feedback, and PHP validates submitted data on the server.
- **SQL injection prevention:** PDO prepared statements pass user input separately from SQL queries.
- **Session protection:** PHP uses strict session mode and regenerates the session ID after login and signup. Sessions expire after five minutes of inactivity.
- **Login lockout:** Five failed login attempts lock the account for 15 minutes.
- **Cookie protection:** Session and Remember Email cookies use `HttpOnly` and `SameSite=Lax`.
- **Audit logging:** The database records successful logins, failed attempts, lockouts, and explicit logouts.
- **XSS hardening:** PHP escapes text in views. Dashboard chart labels still need a separate fix.
