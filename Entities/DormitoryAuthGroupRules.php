<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DormitoryAuthGroupRules extends Model
{
    use HasFactory,SerializeDate;

    protected $table = 'dormitory_auth_group_rules';

}
