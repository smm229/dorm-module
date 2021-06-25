<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryBuildingDevice extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table = 'dormitory_building_device';

    protected $appends = ['type_id','type_name'];

    public function getTypeIdAttribute(){
        return $this->type;
    }

    public function getTypeNameAttribute(){
      if($this->type == 2){
          return "摄像头";
      }else{
          return "门禁控制板";
      }
    }

    public function build(){
        return $this->belongsTo(DormitoryGroup::class,'groupid','groupid');
    }
}
