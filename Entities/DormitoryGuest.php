<?php

namespace Modules\Dorm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryGuest extends Model
{
    use HasFactory;

    protected $table = 'dormitory_guest';

}
