<?php

namespace Modules\Dorm\Entities;

use App\Models\Campus;
use App\Models\Classes;
use App\Models\College;
use App\Models\Major;
use App\Models\Student;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 电控记录
 * Class DormitoryElectric
 * @package Modules\Dorm\Entities
 */
class DormitoryElectric extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_electric';

    protected $appends = ['username'];//['username','campusname','college_name','major_name','class_name'];

    /**
     * 学员名称
     */
    public function getUsernameAttribute()
    {
        return $this->idnum ? Student::where('idnum',$this->idnum)->value('username') : '';
    }

    //校区名称
    /*public function getCampusnameAttribute()
    {
        return Campus::whereId($this->campusid)->value('name');
    }

    //院系名称
    public function getCollegeNameAttribute()
    {
        return College::whereId($this->collegeid)->value('name');
    }

    //专业名称
    public function getMajorNameAttribute()
    {
        return Major::whereId($this->majorid)->value('name');
    }

    //班级名称
    public function getClassNameAttribute()
    {
        return Classes::whereId($this->classid)->value('name');
    }

    //楼宇
    public function dormitory_build(){
        return $this->belongsTo(DormitoryGroup::class,'buildid');
    }*/

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
