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
    function login()
    {
        if ($this->user()) {
            $this->go('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $pw = $_POST['password'] ?? '';
            if (!is_string($email) || !is_string($pw)) {
                $this->flash('error', 'Wrong email or password');
                $this->go('login');
            }
            $email = strtolower(trim($email));
            $remember = !empty($_POST['remember_email']);
            $u = $this->users->findByEmail($email);

            if ($email == '' || $pw == '') {
                $this->flash('error', 'Please fill in all fields');
            } elseif ($u && $this->users->isLocked($u['user_id'])) {
                $this->logs->add($u['user_id'], $email, 'login_failed');
                $this->flash('error', 'Account locked, try again after 15 minutes');
            } elseif (!$u || strlen($pw) > 72 || strpos($pw, "\0") !== false || !password_verify($pw, $u['password'])) {
                $this->logs->add($u ? $u['user_id'] : null, $email, 'login_failed');
                if ($u) {
                    $this->users->addFailed($u['user_id']);
                    if ($this->users->isLocked($u['user_id'])) {
                        $this->logs->add($u['user_id'], $email, 'lockout');
                    }
                }
                $this->flash('error', 'Wrong email or password');
            } else {
                // The password matched the saved hash. Start a fresh login session.
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
            if (!is_string($_POST['name'] ?? '') || !is_string($_POST['email'] ?? '')) {
                $this->flash('error', 'Please enter a valid name and email');
                $this->go('signup');
            }
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pw = $_POST['password'] ?? '';
            $err = $this->check($name, $email, $pw);
            // Public signup always makes a student account.
            if (!$err && strpos(explode('@', $email)[0], '_') === false) {
                $err = 'Use your student email. Professor accounts are created by an admin.';
            }
            if (!$err && $pw !== ($_POST['password2'] ?? '')) {
                $err = 'Passwords dont match';
            }
            if ($err) {
                $this->flash('error', $err);
            } else {
                $id = $this->users->create($name, $email, $pw, 'student');
                session_regenerate_id(true);
                $_SESSION['uid'] = $id;
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
            if (!is_string($_POST['name'] ?? '') || !is_string($_POST['email'] ?? '')) {
                $this->flash('error', 'Please enter a valid name and email');
                $this->go('admin');
            }
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pw = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $err = $this->check($name, $email, $pw);
            if (!$err && !in_array($role, ['admin', 'professor'], true)) {
                $err = 'Pick a valid account type';
            }
            if ($err) {
                $this->flash('error', $err);
            } else {
                $this->users->create($name, $email, $pw, $role);
                $this->flash('success', 'Account created');
            }
            $this->go('admin');
        }
        $this->view('admin');
    }

    // returns error message or null if ok
    private function check($name, $email, $pw)
    {
        if (!is_string($pw)) return 'Please enter a valid password';
        if ($name == '' || $email == '' || $pw == '') {
            return 'Please fill in all fields';
        }
        if (!preg_match('/^[a-z0-9._-]+@dlsu\.edu\.ph$/', $email)) {
            return 'Please use a valid DLSU email';
        }
        $error = passCheck($pw);
        if ($error) return $error;
        if (strlen($pw) > 72 || strpos($pw, "\0") !== false) {
            return 'Password must be at most 72 bytes and cannot contain a null character';
        }
        if ($this->users->findByEmail($email)) {
            return 'Email is already registered';
        }
        return null;
    }
}