<?php

namespace Modules\Dorm\Entities;

use App\Models\Student;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Dorm\Jobs\SyncLink;

class DormitoryStayrecords extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table="dormitory_stayrecords";

    protected $fillable = [];

    /*
     * 登记记录
     * @param data 床位记录，对象/数组
     * @param type 类型 1分配宿舍 2调宿 3退宿
     * @param buildid 原来的宿舍id，调宿用
     */
    public static function record($data,$type,$buildid){
        if($type==3){ //退宿
            self::reverse($data);
        }else{
            $user = Student::where('idnum',$data->idnum)->first();
            if(!$user) return;
            if($type==2){ //调宿舍
                $info = self::where('idnum',$data->idnum)->orderBy('id','desc')->first();
                if($info) self::whereId($info->id)->update(['updated_at'=>date('Y-m-d H:i:s')]);
                //解除关系
                SyncLink::dispatch($user->senselink_id,$buildid,2)->delay(3);
            }
            $floornum = DormitoryRoom::whereId($data->roomid)->value('floornum');
            $arr = [
                'username'      =>  $user->username,//姓名
                'idnum'         =>  $data->idnum,//学号
                'sex'           =>  $user->sex_name,//性别
                'gradeName'     =>  $user->grade,//年级
                'collegeName'   =>  $user->collegename,//学院
                'majorName'     =>  $user->majorname,//专业
                'className'     =>  $user->classname,//班级
                'buildName'     =>  $data->build_name,//楼栋
                'floor'         =>  $floornum,//楼层
                'roomnum'       =>  $data->room_num,//房间号
                'bednum'        =>  $data->bednum,//床位号
            ];
            self::insert($arr);
            //绑定关系
            SyncLink::dispatch($data->senselink_id,$data->buildid,1)->delay(5);
        }

    }

    /*
     * 批量退宿记录
     */
    public static function reverse($data){
        foreach($data as $v){
            $info = self::where('idnum',$v->idnum)->orderBy('id','desc')->first();
            if($info) self::whereId($info->id)->update(['updated_at'=>date('Y-m-d H:i:s')]);
            //解除关系
            $senselink_id = Student::where('idnum',$data->idnum)->value('senselink_id');
            SyncLink::dispatch($senselink_id,$v->buildid,2);
        }

    }
}
