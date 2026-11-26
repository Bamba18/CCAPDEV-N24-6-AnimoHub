changelogs MP PH3

giancarlo added:
-password requirements: 8 char minimum, upper/lowecase chars, number, special char, checked in browser and server.
-session timeout: log u out 5 mins of inactivity
-login attempt lock: 5 wrong attempts and you are locked for 15 mins
-login log (part of audit trail) will record into the db suc logins, fail logins, lockout, and logout
-session id regen: new sesh id at login and signup. for session fixation
-secure cookie settings : httponly block javascript access. same site=lax to limit cross site requests, cookie no expiry
-strict sesh mode : reject sesh id that the server didn't make

rance added:
-server-side form validation: reusable Validator class checking required fields, max lengths, real dates, valid times, and allowed choices
-form validation coverage: applied to announcements, events, org description, and member/officer application and resignation reasons
-front-end form validation: block whitespace-only fields and show a toast message
-xss prevention: escape both double and single quotes in e() helper
-unit tests: 18 test cases covering all 5 Validator methods and firstError() helper