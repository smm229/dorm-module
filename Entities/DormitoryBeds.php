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
    protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_beds';

    protected $fillable = [];
    
//    protected static function newFactory()
//    {
//        return \Modules\Dorm\Database\factories\DormitoryBedsFactory::new();
//    }

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
