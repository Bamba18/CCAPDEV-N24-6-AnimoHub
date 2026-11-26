<?php

class AuthController extends Controller
{
    private const REMEMBER_EMAIL_COOKIE = 'animohub_remember_email';
    private $logs;

    function __construct($db)
    {
        parent::__construct($db);
        $this->logs = new loginLog($db);
    }
    // checks if https
    private function isHttps(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        );
    }
    // email cookie rememberrr
    private function setRememberedEmailCookie(string $email): void
    {
        if (!preg_match('/^[a-z0-9._-]+@dlsu\.edu\.ph$/', $email)) {
            return;
        }

        setcookie(self::REMEMBER_EMAIL_COOKIE, $email, [
            'expires' => time() + (86400 * 14), // 14 days
            'path' => '/',
            'domain' => '',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    // clears cookie
    private function clearRememberedEmailCookie(): void
    {
        setcookie(self::REMEMBER_EMAIL_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    // edits were made w/ cookies in mind; hashing implementations will be underway via ivan's implementations
    function login()
    {
        if ($this->user()) {
            $this->go('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pw = $_POST['password'] ?? '';
            $remember = !empty($_POST['remember_email']);
            $u = $this->users->findByEmail($email);

            if ($email == '' || $pw == '') {
                $this->flash('error', 'Please fill in all fields');
            } elseif ($u && $this->users->isLocked($u['user_id'])) {
                $this->logs->add($u['user_id'], $email, 'login_failed');
                $this->flash('error', 'Account locked, try again after 15 minutes');
            } elseif (!$u || $u['password'] != $pw) {
                $this->logs->add($u ? $u['user_id'] : null, $email, 'login_failed');
                if ($u) {
                    $this->users->addFailed($u['user_id']);
                    if ($this->users->isLocked($u['user_id'])) {
                        $this->logs->add($u['user_id'], $email, 'lockout');
                    }
                }
                $this->flash('error', 'Wrong email or password');
            } else {
                $this->users->resetFailed($u['user_id']);
                session_regenerate_id(true);
                $_SESSION['uid'] = $u['user_id'];

                if ($remember) {
                    $this->setRememberedEmailCookie($email);
                } else {
                    $this->clearRememberedEmailCookie();
                }

                $this->logs->add($u['user_id'], $email, 'login_success');
                $this->flash('success', 'Welcome ' . $u['name'] . '!');
                $this->go('dashboard');
            }
        }

        $rememberedEmail = '';
        $rawCookie = $_COOKIE[self::REMEMBER_EMAIL_COOKIE] ?? '';
        if (is_string($rawCookie)) {
            $candidate = strtolower(trim($rawCookie));
            if (preg_match('/^[a-z0-9._-]+@dlsu\.edu\.ph$/', $candidate)) {
                $rememberedEmail = $candidate;
            }
        }

        $this->view('auth', [
            'tab' => 'login',
            'rememberedEmail' => $rememberedEmail,
        ]);
    }

    function signup()
    {
        if ($this->user()) {
            $this->go('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pw = $_POST['password'] ?? '';
            $role = $this->detectRole($email);
            $err = $this->check($name, $email, $pw);
            if (!$err && !$role) {
                $err = 'Please use a valid DLSU email';
            }
            if (!$err && $pw != ($_POST['password2'] ?? '')) {
                $err = 'Passwords dont match';
            }
            if ($err) {
                $this->flash('error', $err);
            } else {
                session_regenerate_id(true);
                $_SESSION['uid'] = $this->users->create($name, $email, $pw, $role);
                $this->setRememberedEmailCookie($email);
                $this->flash('success', 'Account created!');
                $this->logs->add($_SESSION['uid'], $email, 'login_success');
                $this->go('dashboard');
            }
        }

        $this->view('auth', ['tab' => 'signup']);
    }

    function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $u = $this->user();
            if ($u) {
                $this->logs->add($u['user_id'], $u['email'], 'logout');
            }
            $this->clearRememberedEmailCookie();
            session_destroy();
        }
        $this->go('login');
    }

    // create admin account form
    function admin()
    {
        $this->needAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pw = $_POST['password'] ?? '';
            $err = $this->check($name, $email, $pw);
            if ($err) {
                $this->flash('error', $err);
            } else {
                $this->users->create($name, $email, $pw, 'admin');
                $this->flash('success', 'Admin account created');
            }
            $this->go('admin');
        }
        $this->view('admin');
    }

    // student has _ and professor has .
    private function detectRole($email)
    {
        $local = explode('@', $email)[0];
        if (strpos($local, '_') !== false) {
            return 'student';
        }
        if (strpos($local, '.') !== false) {
            return 'professor';
        }
        return null;
    }

    // returns error message or null if ok
    private function check($name, $email, $pw)
    {
        if ($name == '' || $email == '' || $pw == '') {
            return 'Please fill in all fields';
        }
        if (!preg_match('/^[a-z0-9._-]+@dlsu\.edu\.ph$/', $email)) {
            return 'Please use a valid DLSU email';
        }
        if (passCheck($pw)) {
            return passCheck($pw);
        }
        if ($this->users->findByEmail($email)) {
            return 'Email is already registered';
        }
        return null;
    }
}