<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryNebula extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_nebula';


}
