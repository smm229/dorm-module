<?php

namespace Modules\Dorm\Entities;

use App\Models\Category;
use App\Models\Student;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryLeave extends Model {

    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = "dormitory_leave";

    protected $appends = ['username','type_name'];

    public function getUserNameAttribute()
    {
        return Student::where('idnum',$this->idnum)->value('username');
    }

    //请假类型
    public function getTypeNameAttribute()
    {
        return Category::where(['ckey'=>'leaves','id'=>$this->type])->value('name');
    }

    //学生信息
   public function student(){
       return $this->belongsTo(Student::class,'idnum','idnum');
   }
}
