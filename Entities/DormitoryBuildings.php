<?php

namespace Modules\Dorm\Entities;

use App\Models\Teacher;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryBuildings extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    protected $connection = 'mysql_dorm';

    protected $table = "dormitory_buildings";

    protected $appends = ['total_room','total_beds','total_person','total_empty_beds'];

    //全部房间
    public function getTotalRoomAttribute(){

    }
    //全部床位
    public function getTotalBedsAttributes()
    {

    }

    //入住人数
    public function getTotalPersonAttributes()
    {

    }

    //空床位
    public function getTotalEmptyBedsAttributes()
    {

    }

//    protected static function newFactory()
//    {
//        return \Modules\Dorm\Database\factories\DormitoryBuildingsFactory::new();
//    }


    /*
     * 关联楼宇类型
     */
    public function dormitory_category(){
        return $this->belongsTo(DormitoryCategory::class,'buildtype');
    }

    /*
     * 宿管老师
     */
    public function dormitory_users(){
        /*
         * 第一个参数：要关联的表对应的类
         * 第二个参数：中间表的表名
         * 第三个参数：当前表跟中间表对应的外键
         * 第四个参数：要关联的表跟中间表对应的外键
         * */
        return $this->belongsToMany(DormitoryUsers::class,'dormitory_users_building','buildid','idnum');
    }
}
