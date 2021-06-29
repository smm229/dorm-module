<?php

namespace Modules\Dorm\Entities;

use App\Models\Campus;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryRoom extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table="dormitory_room";

    protected $appends = ['building_name','buildtype_name','campusname'];

    public function getCampusnameAttribute(){
        return Campus::whereId($this->campusid)->value('name');
    }
    //楼宇名称
    public function getBuildingNameAttribute()
    {
        return DormitoryGroup::whereId($this->buildid)->value('title');
    }

    //类型名称
    public function getBuildtypeNameAttribute()
    {
        return DormitoryCategory::whereId($this->buildtype)->where('ckey','dormitory_room')->value('name');
    }

//    protected static function newFactory()
//    {
//        return \Modules\Dorm\Database\factories\DormitoryRoomFactory::new();
//    }

    /*
     * 关联楼宇
     */
    public function dormitory_buildings(){
        return $this->belongsTo(DormitoryGroup::class,'buildid');
    }

    /*
     * 关联床位
     */
    public function dormitory_beds(){
        return $this->hasMany(DormitoryBeds::class,'roomid');
    }
}
