<?php

/* Author by xiangyang
 * Email 648128278@qq.com
 * datetime 2021/05/19
 */
#################################################################
/**************     床位信息导入模板格式      ******************/
/***************************************************************/
/*|  学号  |  姓名  |  宿舍楼  |  楼层  |  宿舍号  |  床位号  |*/
/***************************************************************/
################################################################

namespace Modules\Dorm\Imports;

use App\Imports\FaildImport;
use App\Models\ImportLog;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryChangeApply;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Entities\DormitoryStayrecords;
use Modules\Dorm\Entities\DormitoryUsersGroup;
use senselink;

class BedsImport implements ToArray
{
    private $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param array $array
     * |  学号  |  姓名  |  宿舍楼  |  楼层  |  宿舍号  |  床位号  |
     */
    public function array(Array $array)
    {
        $new_data = [];
        $i = 0;
        $senselink = new senselink();
        $total = count($array)-1;//总的数据
        foreach ($array as $k=>$row){
            if (empty($row[0])) {
                continue;
            }
            if($k==0) continue;
            try {
                //判断学号
                if(!$row[0]){
                    throw new \Exception('学号错误');
                }
                //判断学号是否重复
                $info = Student::whereIdnum($row[0])->first();
                if(!$info){ // 不存在员工
                    //异常
                    throw new \Exception('学号不存在');
                }
                if(DormitoryBeds::where('idnum',$row[0])->first()){
                    throw new \Exception('学员已分配宿舍');
                }
                if(DormitoryChangeApply::where(['idnum'=>$row[0],'status'=>1])->first()){
                    throw new \Exception('存在审核中的申请');
                }
                //判断姓名
                if(!$row[1]){
                    throw new \Exception('姓名错误');
                }
                if(!$row[2]){
                    throw new \Exception('缺少宿舍楼');
                }
                $build = DormitoryGroup::where('type',1)->where('title',$row[2])->first();
                if(!$build){
                    throw new \Exception('宿舍楼不存在');
                }
                $room = DormitoryRoom::where('buildid',$build->id)->where('floornum',$row[3])->where('roomnum',$row[4])->first();
                if(!$room){
                    throw new \Exception('对应楼层的宿舍不存在');
                }
                $bed = DormitoryBeds::where('buildid',$build->id)->where('roomid',$room->id)->where('bednum',$row[5])->first();
                if(!$bed){
                    throw new \Exception('床位信息不存在');
                }
                //分配宿舍
                DB::beginTransaction();
                try {
                    DormitoryBeds::whereId($bed->id)->update(['idnum' => $row[0], 'is_in' => 2]);
                    //同步宿舍信息到学生中心
                    Student::whereId($info->id)->update([
                        'dorminfo' => $bed->build_name . $bed->floornum . $bed->room_num . $bed->bednum,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    DormitoryChangeApply::insert([
                        'idnum'     => $row[0],
                        'username'  => $info->username,
                        'buildid'   => $bed->buildid,
                        'floornum'  => $bed->floornum,
                        'roomid'    => $bed->roomid,
                        'roomnum'   => $bed->room_num,
                        'bednum'    => $bed->bednum,
                        'status'    => 2
                    ]);
                    $arr = [
                        'username'      => $info->username,//姓名
                        'idnum'         => $info->idnum,//学号
                        'sex'           => $info->sex_name,//性别
                        'gradeName'     => $info->grade,//年级
                        'campusname'    => $info->campusname,
                        'collegeName'   => $info->collegename,//学院
                        'majorName'     => $info->majorname,//专业
                        'className'     => $info->classname,//班级
                        'buildName'     => $bed->build_name,//楼栋
                        'floor'         => $bed->floornum,//楼层
                        'roomnum'       => $bed->room_num,//房间号
                        'bednum'        => $bed->bednum,//床位号
                    ];
                    DormitoryStayrecords::insert($arr);
                    $groupid = DormitoryGroup::whereId($bed->buildid)->value('groupid');
                    if(!DormitoryUsersGroup::where(['groupid'=>$groupid,'senselink_id'=>$info->senselink_id])->first()){
                        //添加关联组
                        DormitoryUsersGroup::insert(['groupid'=>$groupid,'senselink_id'=>$info->senselink_id]);
                    }
                    //同步添加到link
                    $res = $senselink->linkperson_addgroup($info->senselink_id,$groupid);
                    if($res['code']!=200){ //添加失败
                        throw new \Exception($res['message']);
                    }
                    DB::commit();
                }catch(\Exception $e){
                    DB::rollBack();
                    throw new \Exception($e->getMessage());
                }
            }catch(\exception $e){
                Log::channel('link')->error($this->filename.'导入住宿信息错误日志：'.$e->getMessage());
                $row[6] = $e->getMessage();
                $new_data[$i] = $row;
                $i++;
            }
        }
        $date = date('Ymd');
        $filePath = 'file/' . $date . '/' . $this->filename;
        //导入记录
        if(!ImportLog::where('filename','/uploads/'.$filePath)->first()){
            ImportLog::insert(['total'=>$total,'fails'=>$i,'filename'=>'/uploads/'.$filePath]);

            if($new_data){ //失败数据，写入excel
                $header = [['学号', '姓名', '宿舍楼', '楼层', '宿舍号', '床位号', '错误信息']];
                Excel::store(new FaildImport($header,$new_data), $filePath,'public');
            }
        }
    }
}
