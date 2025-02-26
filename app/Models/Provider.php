<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'name',
        'document_number',
        'address',
        'phone',
        'country',
        'city',
        'SAP_code'
    ];
}
