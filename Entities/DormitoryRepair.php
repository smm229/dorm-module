<?php

namespace Modules\Dorm\Entities;

use App\Models\Student;
use App\Models\Teacher;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryRepair extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_repair';

    protected $appends = ['build_name'];

    //楼宇名称
    public function getBuildNameAttribute(){
        return DormitoryGroup::whereId($this->buildid)->value('title');
    }

    //图集
    public function getCoversAttribute($value){
        $this->attributes['covers'] = json_decode($value,true);
    }
    /*
     * 关联学生,其他库
     */
    public function student(){
        return $this->belongsTo(Student::class,'idnum','idnum');
    }

    /*
     * 关联宿管
     */
    public function teacher(){
        return $this->belongsTo(Teacher::class,'teacher_idnum','idnum');
    }

    /*
     * 关联楼宇
     */
    public function build(){
        return $this->belongsTo(DormitoryGroup::class,'buildid');
    }
}
