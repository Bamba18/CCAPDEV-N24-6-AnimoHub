<?php
//AUDIT TRAIL : login log should log what happens with logins success,fail,lockout or logout

class loginLog{
  private $db;
  function __construct($db) {$this->db = $db;}
//action login suc, login fail, etc.
function add($userId, $email, $action){
  $st = $this->db->prepare("INSERT INTO auth_log (user_id, email, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
  $st->execute([
               $userId,
               substr($email, 0, 100),
               $action,
               $_SERVER['REMOTE_ADDR'] ?? '',
               substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
               ]);
  }
}
