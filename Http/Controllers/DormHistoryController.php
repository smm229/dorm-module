<?php

namespace Modules\Dorm\Http\Controllers;

use App\Exports\Export;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Log;
use Excel;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryNoBackRecord;
use Modules\Dorm\Entities\DormitoryStayrecords;

//历史记录
class DormHistoryController extends Controller
{

    /*
     * 住宿历史列表
     * @param username string 姓名
     * @param idnum string 学号
     */
    public function lists(Request $request){
        $pagesize = $request->pagesize ?? 10;
        $buildids = RedisGet('builds-'.auth()->user()->id);
        $list = DormitoryStayrecords::whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->username) $q->whereUsername($request->username);
                if($request->idnum) $q->whereUsername($request->idnum);
            })
            ->orderBy('id','desc')
            ->paginate($pagesize);
        return showMsg('成功',200,$list);
    }
    /*
     * 导出住宿历史
     */
    public function export(Request $request){
        $header = [[
            'idnum'             =>    '学号',
            'username'          =>    '姓名',
            'sex'          =>    '性别',
            'buildName'         =>    '宿舍楼',
            'roomnum'           =>    '房间号',
            'bednum'            =>    '床位号',
            'collegeName'       =>    '学院',
            'majorName'         =>    '专业',
            'gradeName'         =>    '年级',
            'created_at'        =>    '入住时间',
            'updated_at'        =>    '退宿时间'
        ]];
        $buildids = RedisGet('builds-'.auth()->user()->id);
        $data = DormitoryStayrecords::whereIn('buildid',$buildids)
            ->get()->toArray();
        $excel = new Export($data, $header,'住宿历史');
        return Excel::download($excel, time().'.xlsx');
    }

    /*
     * 学生通行记录
     * @param type int 1学生2教师
     * @param username string 姓名
     * @param idnum string 学号
     * @param begin_time string 开始时间
     * @param end_time string 截止时间
     * @param pagesize int 每页数量
     */
    public function student_access(Request $request){
        $type = $request->type ?? 1;
        $pagesize = $request->pagesize ?? 10;
        $buildids = RedisGet('builds-'.auth()->user()->id);
        $list = DormitoryAccessRecord::whereIn('buildid',$buildids)
            ->whereType($type)
            ->whereStatus(0)
            ->where(function ($q) use ($request){
                if($request->begin_time) $q->where('pass_time','>=',$request->begin_time);
                if($request->end_time) $q->where('pass_time','<=',$request->end_time);
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->username) $q->whereUsername($request->username);
            })
            ->orderBy('id','desc')
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

    /*
     * 正常通行记录导出
     */
    public function access_export(Request $request){
        $type = $request->type ?? 1;//1学员 2教职工
        $header = [[
            'idnum'             =>    $type==1 ? '学号' : '教职工',
            'username'          =>    '姓名',
            'sex'          =>    '性别',
            'college_name'       =>    '学院',
            'major_name'         =>    '专业',
            'grade_name'         =>    '年级',
            'class_name'         =>    '班级',
            'pass_location'     =>    '通行地点',
            'pass_way'          =>     '通道名称',
            'direction'         =>     '方向',
            'abnormalType'     =>      '通行状态',
            'pass_time'        =>    '通行时间'
        ]];
        $buildids = RedisGet('builds-'.auth()->user()->id);
        $data = DormitoryAccessRecord::whereIn('buildid',$buildids)
            ->whereType($type)
            ->whereStatus(0)
            ->get()
            ->toArray();
        $excel = new Export($data, $header,'通行记录');
        return Excel::download($excel, time().'.xlsx');
    }

    /*
     * 晚归记录
     * @paran  username  string 姓名
     * @param idnum string  学号
     * @param start_date 开始日期
     * @param end_date 开始日期
     */
    public function later(Request $request){
        $start_date = $request->start_date ?? date('Y-m-d');
        $end_date = $request->end_date ?? date('Y-m-d 23:59:59');
        $buildids = RedisGet('builds-'.auth()->user()->id);
        $pagesize = $request->pagesize ?? 10;
        $list = DormitoryAccessRecord::whereType(1)
            ->whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->username) $q->whereUsername($request->username);
                if($request->idnum) $q->whereIdnum($request->idnum);
            })
            ->whereStatus(1)
            ->whereBetween('pass_time',[$start_date,$end_date])
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

    /*
     * 未归记录
     * @paran  username  string 姓名
     * @param idnum string  学号
     * @param start_date 开始日期
     * @param end_date 开始日期
     */
    public function noBack(Request $request){
        $pagesize = $request->pagesize ?? 10;
        $start_date = $request->start_date ?? date('Y-m-d');
        $end_date = $request->end_date ?? date('Y-m-d');
        $buildids = RedisGet('builds-'.auth()->user()->id);
        $list = DormitoryNoBackRecord::whereIn('buildid',$buildids)
            ->whereBetween('date',[$start_date,$end_date])
            ->whereType(1)
            ->paginate($pagesize);

        return showMsg('获取成功',200,$list);
    }

}