<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryRoom extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    protected $connection = 'mysql_dorm';

    protected $table="dormitory_room";

    protected $fillable = [];
    
//    protected static function newFactory()
//    {
//        return \Modules\Dorm\Database\factories\DormitoryRoomFactory::new();
//    }

    /*
     * 关联楼宇
     */
    public function dormitory_buildings(){
        return $this->belongsTo(DormitoryBuildings::class,'buildid');
    }
}
