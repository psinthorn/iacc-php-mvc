<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\User;

class UserRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new User());
    }
    public function findByUsername($username) {
        return $this->findBy('username', $username);
    }
    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }
    public function getActiveUsers() {
        return array_filter($this->all(), fn($u) => $u->status == 1);
    }
}
