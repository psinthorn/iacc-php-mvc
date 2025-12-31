<?php

namespace App\Models;

/**
 * Company Model
 * 
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $address
 * @property string $phone
 * @property string $email
 * @property string $tax_id
 * @property int $status
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Company extends Model
{
    protected $table = 'company';
    protected $fillable = ['name', 'code', 'address', 'phone', 'email', 'tax_id', 'status'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'status' => 'int'];

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'company_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'company_id');
    }
}
