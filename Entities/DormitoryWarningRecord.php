<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryWarningRecord extends Model
{
    use HasFactory,SerializeDate;
    //protected $connection = "mysql_dorm";
    protected $table = "dormitory_warning_record";
    protected $fillable = [];

    protected $appends = ['type','direction_name'];

    //名称
    public function getTypeAttribute()
    {
        return '非法闯入';
    }

    public function getDirectionNameAttribute()
    {
        $direction = ['1'=>'进', '2'=>'出', ''=> ''];
        return $direction[$this->direction];
    }

}
