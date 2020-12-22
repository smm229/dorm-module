<?php

namespace Modules\Dorm\Entities;

use App\Models\Student;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryBeds extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_beds';

    protected $appends = ['check_in','username','build_name','room_num']; //是否入住，学员名称,楼宇名称，房间名称

    //是否有学员入住
    public function getCheckInAttribute()
    {
        return $this->idnum ? 1 : 0;
    }

    /*
     * 学员名称
     */
    public function getUsernameAttribute()
    {
        return $this->idnum ? Student::where('idnum',$this->idnum)->value('username') : '';
    }

    //楼宇名称
    public function getBuildNameAttribute()
    {
        return DormitoryGroup::whereId($this->buildid)->value('title');
    }

    //房间信息
    public function getRoomNumAttribute()
    {
        return DormitoryRoom::whereId('roomid')->value('roomnum');
    }

    /*
     * 关联宿舍
     */
    public function dormitory_room(){
        return $this->belongsTo(DormitoryRoom::class,'roomid');
    }

    /*
     * 关联学生,其他库
     */
    public function student(){
        return $this->belongsTo(Student::class,'idnum','idnum');
    }

}
