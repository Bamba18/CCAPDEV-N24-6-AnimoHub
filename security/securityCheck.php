<?php

define('TIMEOUT', 5 * 60); //will kick you out after 5 mins no action

//sesh start
function startSecureSession(){
  ini_set('session.use_strict_mode', 1);
  session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
  session_start();

if(isset($_SESSION['uid']) && time() - ($_SESSION['last_activity'] ?? 0) > TIMEOUT){
  $_SESSION=[];
  session_regenerate_id(true);
  $_SESSION['flash'] = ['error', 'session expired please login again'];
  header('Location: index.php?page=login');
  exit;
}
  $_SESSION['last_activity'] = time();
}

//password requirements check
function passCheck($pw){
  if (strlen($pw) < 8) return 'Password must be at least 8 characters';
    if (!preg_match('/[A-Z]/', $pw)) return 'Password needs an uppercase letter';
    if (!preg_match('/[a-z]/', $pw)) return 'Password needs a lowercase letter';
    if (!preg_match('/[0-9]/', $pw)) return 'Password needs a number';
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) return 'Password needs a special character';
    return null;
}
