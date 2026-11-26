// Front-end form validation.
// The browser's "required" lets a field with only spaces through, so we check that here.
// The server checks everything again (security/validator.php), this is just for quick feedback.
document.addEventListener('submit', function (ev) {
  var form = ev.target;
  var fields = form.querySelectorAll('input[required], textarea[required]');

  for (var i = 0; i < fields.length; i++) {
    var f = fields[i];
    // passwords are skipped on purpose, the password checks are handled somewhere else
    if (f.type === 'password' || f.type === 'hidden') continue;

    if (f.value.trim() === '') {
      ev.preventDefault();                       // stop the form from sending
      toast('Please fill in all required fields', 'error');  // toast() is in main.js
      f.focus();
      return;
    }
  }

  // event dates must be a real date, like 2026-11-30
  var date = form.querySelector('input[type="date"]');
  if (date && date.value && isNaN(Date.parse(date.value))) {
    ev.preventDefault();
    toast('Please enter a valid date', 'error');
    date.focus();
  }
});
