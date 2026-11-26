<?php
// parent of all controllers
class Controller {
    protected $db;
    protected $users;

    function __construct($db) {
        $this->db = $db;
        $this->users = new UserModel($db);
    }

    protected function view($name, $data = []) {
        $user = $this->user();
        $manages = $user ? $this->users->canManage($user) : false;
        extract($data);
        require "views/$name.php";
    }

    protected function flash($type, $msg) { $_SESSION['flash'] = [$type, $msg]; }

    protected function go($page) {
        header("Location: index.php?page=$page");
        exit;
    }

    protected function user() {
        if (!isset($_SESSION['uid'])) return null;
        $u = $this->users->findById($_SESSION['uid']);
        if (!$u || !in_array($u['role'], ['admin', 'professor', 'student'], true)) {
            unset($_SESSION['uid']);
            return null;
        }
        return $u;
    }

    protected function needLogin() {
        $u = $this->user();
        if (!$u) $this->go('login');
        return $u;
    }

    protected function needAdmin() {
        return $this->needRole('admin');
    }

    // check the role from the database, not a hidden field or a cookie
    protected function needRole($role) {
        $u = $this->needLogin();
        if ($u['role'] !== $role) $this->deny();
        return $u;
    }

    protected function needOrg($orgId, $presidentOnly = false) {
        $u = $this->needLogin();
        if (!$this->users->canManageOrg($u, $orgId, $presidentOnly)) $this->deny();
        return $u;
    }

    protected function deny() {
        http_response_code(403);
        $this->view('forbidden');
        exit;
    }
}
