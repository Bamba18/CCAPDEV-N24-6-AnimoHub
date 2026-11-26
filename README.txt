changelogs MP PH3

giancarlo added:
-password requirements: 8 char minimum, upper/lowecase chars, number, special char, checked in browser and server.
-session timeout: log u out 5 mins of inactivity
-login attempt lock: 5 wrong attempts and you are locked for 15 mins
-login log (part of audit trail) will record into the db suc logins, fail logins, lockout, and logout
-session id regen: new sesh id at login and signup. for session fixation
-secure cookie settings : httponly block javascript access. same site=lax to limit cross site requests, cookie no expiry
-strict sesh mode : reject sesh id that the server didn't make

Password hashing and account access (Phase 3)
- New accounts save a bcrypt hash. Login checks the typed password against that hash.
- For an existing Phase 3 database, back it up first. From this project folder run:
    php database/hash-passwords.php
  Run it before using the updated login. It skips passwords that are already hashed.
  The SQL seed recreates the database. Use it only for a fresh demo installation.
- Public signup creates students. An admin creates professor and admin accounts.
- Students manage their own enlistment. Officers manage content in their own org.
  Only that org's president or an admin can review requests or manage member roles.
- Sample accounts keep their original classroom demo passwords. Set private passwords
  for privileged accounts before using sample data on a public server.
