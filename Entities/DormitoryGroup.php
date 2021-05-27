<?php

namespace Modules\Dorm\Entities;

use App\Models\Student;
use App\Models\Teacher;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryGroup extends Model
{
    use HasFactory,SerializeDate;
    const DORMTYPE  = 1;
    const GROUPTYPE = 2;
    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = "dormitory_group";

    protected $appends = ['total_room','total_beds','total_person','total_empty_beds','buildtype_name', 'allin_person', 'devices'];

    //全部房间
    public function getTotalRoomAttribute(){
        return DormitoryRoom::where('buildid',$this->id)->count();
    }
    //全部床位
    public function getTotalBedsAttribute()
    {
        return DormitoryBeds::where('buildid',$this->id)->count();
    }

    //宿舍入住人数
    public function getTotalPersonAttribute()
    {
        return DormitoryBeds::where('buildid',$this->id)->whereNotNull('idnum')->count();
    }

    //权限组内总人数
    public function getAllinPersonAttribute()
    {
        return DormitoryUsersGroup::where('groupid', $this->groupid)->count();
    }

    //空床位
    public function getTotalEmptyBedsAttribute()
    {
        return DormitoryBeds::where('buildid',$this->id)->whereNull('idnum')->count();
    }

    //类型名称
    public function getBuildtypeNameAttribute()
    {
        return DormitoryCategory::whereId($this->buildtype)->where('ckey','dormitory')->value('name');
    }
    //设备信息
    public function getDevicesAttribute()
    {
        return DormitoryBuildingDevice::where('groupid', $this->groupid)->get();
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
        return $this->belongsToMany(DormitoryUsers::class,'dormitory_users_building','buildid','idnum','id','idnum');
    }

    // 床位
    public function beds()
    {
        return $this->hasMany(DormitoryBeds::class, 'buildid', 'id');
    }
}
