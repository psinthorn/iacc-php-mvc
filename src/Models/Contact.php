<?php

namespace App\Models;

/**
 * Contact Model
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $position
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Contact extends Model
{
    protected $table = 'contact';
    protected $fillable = ['company_id', 'name', 'email', 'phone', 'position'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'company_id' => 'int'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
