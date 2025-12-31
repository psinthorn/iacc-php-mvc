<?php namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Contact;

class ContactRepository extends Repository {
    public function __construct(Database $database) {
        parent::__construct($database, new Contact());
    }
    public function getByCompany($companyId) {
        return $this->where('company_id', $companyId);
    }
}
