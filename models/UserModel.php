<?php
class UserModel {
    private $db;

    function __construct($db) { $this->db = $db; }

    function findByEmail($email) {
        $st = $this->db->prepare("SELECT * FROM `user` WHERE email = ?");
        $st->execute([$email]);
        return $st->fetch();
    }

    function findById($id) {
        $st = $this->db->prepare("SELECT user_id, email, name, role FROM `user` WHERE user_id = ?");
        $st->execute([$id]);
        return $st->fetch();
    }

    //phase 3 new sec
    function isLocked($id){
        $st = $this->db->prepare("SELECT COUNT(*) FROM `user` WHERE user_id = ? AND locked_until > NOW()");
        $st->execute([$id]);
        return $st->fetchColumn() >0;
    }
    function addFailed($id){
        $st = $this->db->prepare("UPDATE `user` SET failed_attempts = failed_attempts + 1 WHERE user_id = ?");
        $st->execute([$id]);
        $st= $this->db->prepare("UPDATE `user` SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE), failed_attempts = 0 WHERE user_id = ? AND failed_attempts >= 5");
        $st->execute([$id]);
    }
    function resetFailed($id) {
        $st = $this->db->prepare("UPDATE `user` SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?");
        $st->execute([$id]);
    }
    //ENDDDDDD PH3 EDITS
            
    // adds the user and its admin/professor/student row
    function create($name, $email, $password, $role) {
        if (!in_array($role, ['admin', 'professor', 'student'], true)) {
            throw new InvalidArgumentException('Invalid account role');
        }
        // bcrypt uses at most 72 bytes, so do not silently cut a password
        if (!is_string($password) || $password === '' || strlen($password) > 72 || strpos($password, "\0") !== false) {
            throw new InvalidArgumentException('Invalid password length or characters');
        }
        // Save the hash here so signup and admin-created accounts use the same rule.
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("INSERT INTO `user` (email, password, name, role) VALUES (?, ?, ?, ?)");
            $st->execute([$email, $hash, $name, $role]);
            $id = $this->db->lastInsertId();
            $st = $this->db->prepare("INSERT INTO `$role` (user_id) VALUES (?)");
            $st->execute([$id]);
            $this->db->commit();
            return $id;
        } catch (Exception $ex) {
            $this->db->rollBack();
            throw $ex;
        }
    }

    // true if the user can open org settings
    function canManage($user, $presidentOnly = false) {
        if (!$user) return false;
        if ($user['role'] === 'admin') return true;
        if ($user['role'] !== 'student') return false;
        $permission = $presidentOnly ? "r.role_name = 'President'" : "r.can_manage = 1";
        $st = $this->db->prepare("SELECT COUNT(*) FROM membership m JOIN student s ON s.student_id = m.student_id JOIN org_role r ON r.role_id = m.role_id WHERE s.user_id = ? AND $permission");
        $st->execute([$user['user_id']]);
        return $st->fetchColumn() > 0;
    }

    // being an officer of one org does not give access to the other orgs
    function canManageOrg($user, $orgId, $presidentOnly = false) {
        if (!$user || $orgId <= 0) return false;
        if ($user['role'] === 'admin') {
            $st = $this->db->prepare("SELECT COUNT(*) FROM organization WHERE org_id = ?");
            $st->execute([$orgId]);
            return $st->fetchColumn() > 0;
        }
        if ($user['role'] !== 'student') return false;
        $permission = $presidentOnly ? "r.role_name = 'President'" : "r.can_manage = 1";
        $st = $this->db->prepare("SELECT COUNT(*) FROM membership m
                JOIN student s ON s.student_id = m.student_id
                JOIN org_role r ON r.role_id = m.role_id
                WHERE s.user_id = ? AND m.org_id = ? AND $permission");
        $st->execute([$user['user_id'], $orgId]);
        return $st->fetchColumn() > 0;
    }
}
