<?php

namespace Modules\Dorm\Entities;

use App\Models\Student;
use App\Models\Teacher;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryChangeApply extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_change_apply';

    protected $appends=['build_name'];

    public function getBuildNameAttribute(){
        return DormitoryGroup::whereId($this->buildid)->value('title');
    }
    /*
     * 关联学生,其他库
     */
    public function student(){
        return $this->belongsTo(Student::class,'idnum','idnum');
    }

    /*
     * 关联楼宇
     */
    public function build(){
        return $this->belongsTo(DormitoryGroup::class,'buildid');
    }
}
