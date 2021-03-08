<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DormitoryUsers extends Authenticatable implements JWTSubject {

    use Notifiable,HasFactory,SerializeDate,SoftDeletes;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = "dormitory_users";

    #protected $primaryKey = "idnum";

    protected $fillable = [];

    public function getSexAttribute($value)
    {
        $sex = ['1'=>'男', '2'=>'女', '3' => '保密'];
        return $sex[$value];
    }
    //关联楼宇
    public function dormitory_buildings(){
        /*
         * 第一个参数：要关联的表对应的类
         * 第二个参数：中间表的表名
         * 第三个参数：当前表跟中间表对应的外键
         * 第四个参数：要关联的表跟中间表对应的外键
         * */
        return $this->belongsToMany(DormitoryGroup::class,'dormitory_users_building','idnum','buildid','idnum','id');
    }

    /**
     * 获取会储存到 jwt 声明中的标识
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * 返回包含要添加到 jwt 声明中的自定义键值对数组
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return ['role' => 'dorm'];
    }

}
