<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryWarningRecord extends Model
{
    use HasFactory,SerializeDate;
    protected $connection = "mysql_dorm";
    protected $table = "dormitory_warning_record";
    protected $fillable = [];
    


}
