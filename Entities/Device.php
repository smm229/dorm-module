<?php

namespace Modules\Dorm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $appends = ['direction'];
    protected static function newFactory()
    {
        return \Modules\Dorm\Database\factories\DeviceFactory::new();
    }
}
