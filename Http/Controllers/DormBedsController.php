<?php

namespace Modules\Dorm\Http\Controllers;

use App\Exports\Export;
use App\Models\ImportLog;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryChangeApply;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Http\Requests\DormitoryBedsValidate;
use Log;
use Excel;
use Modules\Dorm\Imports\BedsImport;
use Modules\Dorm\Jobs\Stayrecords;

class DormBedsController extends Controller
{

    public function __construct()
    {
        $this->middleware(['AuthDel'])->only(['del','del_cate']);
    }

    /**
     * 导出
     */
    public function export(Request $request){
        ini_set('memory_limit', '256M');
        $header = [[
            'room_num'      =>    '房间号',
            'bednum'        =>    '床位号',
            'idnum'         =>    '住宿人员编号'
        ]];
        $idnum = auth()->user()->idnum;
        $buildids = RedisGet('builds-'.$idnum);
        $data = DB::table('dormitory_beds')
            ->whereIn('buildid',$buildids)
            ->get(['idnum','room_num','bednum'])
            ->map(function ($value) {
                return (array)$value;
            })->toArray();
        $excel = new Export($data, $header,'床位信息');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }

    /**
     * 床位列表
     * @param buildid int 宿舍楼id
     * @param floornum int 楼层
     * @param roomnum 房间号
     */
    public function lists(Request $request){
        $pagesize = $request->pageSize ?? 12;
        $type = $request->type ?? 1;//类型 1自己查看床位列表 2调宿时查看床位列表
        //当前宿管管理那栋楼
        $idnum = auth()->user()->idnum;
        $buildids = RedisGet('builds-'.$idnum);
        //DB::connection('mysql_dorm')->enableQueryLog();
        $list = DormitoryRoom::whereIn('buildid',$buildids)
            ->where(function ($q) use ($request,$type){
                if($type==2){ //调宿
                    $q->whereExists(function($query)
                    {
                        $query->from('dormitory_beds')->whereRaw('dormitory_room.id = dormitory_beds.roomid')->whereNull('idnum');
                    });
                }

                if($request->buildid) $q->where('buildid',$request->buildid);
                if($request->floornum) $q->where('floornum',$request->floornum);
                //房间号
                if($request->roomnum) $q->where('roomnum',$request->roomnum);
            })
            ->with(['dormitory_beds'=>function ($q){
                $q->select('id','bednum','roomid','idnum')->orderBy('id','asc');
            }])
            ->paginate($pagesize);
        //$queries = DB::connection('mysql_dorm')->getQueryLog();

        return showMsg('获取成功',200,$list);
    }

    /**
     * 床位详情
     */
    public function detail(Request $request){
        if(!$request->roomid){ //宿舍id
            return showMsg('请选择宿舍');
        }
        $list = DormitoryBeds::where('roomid',$request->roomid)
            ->with('student')
            ->orderBy('bednum','asc') //床位号
            ->get();
        return showMsg('获取成功',200,$list);
    }

    /**
     * 调宿
     * @param bedsid int 床位id
     * @param idnum int 学员学号
     * @param type 类型 1分配宿舍 2调宿
     */
    public function change(DormitoryBedsValidate $request){
        $beds = DormitoryBeds::find($request->bedsid);
        $type = $request->type ?? 1; //1分配宿舍 2调宿
        if(!$beds){
            return showMsg('床位不存在');
        }
        if($beds->idnum){ //有人员住
            return showMsg('请选择其他床位');
        }
        //是否有审核中的申请
        if(DormitoryChangeApply::where(['idnum'=>$request->idnum,'status'=>1])->first()){
            return showMsg('存在审核中的申请');
        }
        if(DormitoryBeds::where('idnum',$request->idnum)
            ->first()){
            $type = 2;
        }
        try {
            $buildid = 0;
            if($type==2){ //调宿
                $buildid = DormitoryBeds::where('idnum',$request->idnum)->value('buildid');
            }
            DB::transaction(function () use ($beds,$request,$type){
                if($type==2){ //调宿
                    //删除原来的宿舍分配
                    DormitoryBeds::where('idnum',$request->idnum)->update(['is_in'=>0,'idnum'=>null]);
                }
                //分配学员
                DormitoryBeds::whereId($request->bedsid)->update(['idnum'=>$request->idnum,'is_in'=>2]);
                //同步宿舍信息到学生中心
                $student = Student::where('idnum',$request->idnum)->first();
                Student::whereId($student->id)->update([
                    'dorminfo'=>$beds->build_name.$beds->floornum.$beds->room_num.$beds->bednum,
                    'updated_at'=>date('Y-m-d H:i:s')
                ]);
                DormitoryChangeApply::insert([
                    'idnum'=>$request->idnum,
                    'username'  =>  $student->username,
                    'buildid'   =>  $beds->buildid,
                    'floornum'  =>  $beds->floornum,
                    'roomid'    =>  $beds->roomid,
                    'roomnum'   =>  $beds->room_num,
                    'bednum'    =>  $beds->bednum,
                    'status'    =>  2
                ]);
            });
            //调宿记录
            $beds = DormitoryBeds::find($request->bedsid);
            Queue::push(new Stayrecords($beds,$type,$buildid));
            return showMsg('分配成功', 200);
        }catch(\Exception $e){
            return showMsg('分配失败');
        }
    }

    /**
     * 删除学员,退宿
     */
    public function del(Request $request){
        try {
            $bedids = is_array($request->bedsid) ? $request->bedsid : (array)$request->bedsid;
            $beds = DormitoryBeds::whereIn('id',$bedids)->get()->toArray();
            $idnums = DormitoryBeds::whereIn('id',$bedids)->pluck('idnum')->toArray();
            if(!$beds){
                throw new \Exception('数据格式错误');
            }
            if(DormitoryBeds::whereIn('id', $bedids)->update(['is_in'=>0,'idnum' => null])){
                //同步宿舍信息到学生中心
                Student::whereIn('idnum',$idnums)->update([
                    'dorminfo'=>'',
                    'updated_at'=>date('Y-m-d H:i:s')
                ]);
                file_put_contents(storage_path('logs/stayrecords.log'),'Stayrecords--准备分配数据'.json_encode($beds).PHP_EOL,FILE_APPEND);
                //退宿记录
                Queue::push(new Stayrecords($beds,3,0));
                return showMsg('删除成功', 200);
            }
            return showMsg('删除失败', 201);
        }catch(\Exception $e) {
            return showMsg('删除失败'.$e->getMessage());
        }
    }

    /**
     * 批量退宿获取人员列表
     * @param buildid int 宿舍楼
     * @param floornum int 楼层
     * @param roomid int 房间号
     * @param username string 姓名
     * @param idnum int 学号
     */
    public function users(Request $request){
        $pagesize = $request->pageSize ?? 12;
        //当前宿管查看自己的楼栋
        $idnum = auth()->user()->idnum;
        $buildids = RedisGet('builds-'.$idnum);
        $roomids = DormitoryRoom::whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->buildid) $q->where('buildid',$request->buildid);
                if($request->floornum) $q->where('floornum',$request->floornum);
                //房间号
                if($request->roomnum) $q->where('roomnum',$request->roomnum);
            })
            ->pluck('id')
            ->toArray();
        $list = DormitoryBeds::whereIn('roomid',$roomids)
            ->whereNotNull('idnum')
            ->where(function ($query) use ($request){
                if($request->idnum) $query->where('idnum',$request->idnum);

            })
            ->whereHas('student', function($q) use ($request){
                if($request->username) $q->where('username',$request->username);
            })
            ->with('student')
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

    /**
     * 住宿分配导入
     * @param file excel表
     */
    public function import(Request $request){
        $file = $request->file('file');
        if(!$file){
            return showMsg('请选择上传文件');
        }
        $filename = md5(time()).'.xlsx';
        $date = date('Ymd');
        $filePath = '/uploads/file/'.$date.'/'.$filename;
        try{
            Excel::import(new BedsImport($filename),$file);
        }catch(\Exception $e){
            return showMsg('上传失败'.$e->getMessage());
        }
        $res = ImportLog::where('filename',$filePath)->first();
        return showMsg('上传成功',200,$res);
    }
}
