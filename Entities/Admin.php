<?php

namespace Modules\Dorm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = 'category';
    protected static function newFactory()
    {
        return \Modules\Dorm\Database\factories\AdminFactory::new();
    }

}
