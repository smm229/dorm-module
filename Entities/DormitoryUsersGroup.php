<?php

namespace Modules\Dorm\Entities;

use App\Models\Student;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryUsersGroup extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_users_group';

    /*
     * 关联楼宇类型
     */
    public function student_users(){
        return $this->belongsTo(Student::class,'senselink_id', 'senselink_id');
    }

}
