<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryStrangeAccessRecord extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = "dormitory_strange_access_record";

    protected $appends = ['type'];

    //名称
    public function getTypeAttribute()
    {
        return '陌生人';
    }
    //protected $fillable = [];

}
