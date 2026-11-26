changelogs MP PH3

giancarlo added:
-password requirements: 8 char minimum, upper/lowecase chars, number, special char, checked in browser and server.
-session timeout: log u out 5 mins of inactivity
-login attempt lock: 5 wrong attempts and you are locked for 15 mins
-login log (part of audit trail) will record into the db suc logins, fail logins, lockout, and logout
-session id regen: new sesh id at login and signup. for session fixation
-secure cookie settings : httponly block javascript access. same site=lax to limit cross site requests, cookie no expiry
-strict sesh mode : reject sesh id that the server didn't make
