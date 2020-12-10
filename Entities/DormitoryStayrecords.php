<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Dorm\Jobs\Stayrecords;

class DormitoryStayrecords extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    protected $connection = 'mysql_dorm';

    protected $table="dormitory_stayrecords";

    protected $fillable = [];

}
