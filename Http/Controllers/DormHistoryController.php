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
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryNoBackRecord;
use Modules\Dorm\Entities\DormitoryNoRecord;
use Modules\Dorm\Entities\DormitoryStayrecords;
use Modules\Dorm\Entities\DormitoryStrangeAccessRecord;

//历史记录
class DormHistoryController extends Controller
{

    /**
     * 住宿历史列表
     * @param username string 姓名
     * @param idnum string 学号
     * @param grade string 年级
     * @param start_date string 开始时间
     * @param end_date string 结束时间
     * @param buildName 楼宇名称
     * @param collegeName 学院
     * @param majorName 专业
     * @param className 班级
     */
    public function lists(Request $request){
        $pagesize = $request->pageSize ?? 10;
        $list = DormitoryStayrecords::where(function ($q) use ($request){
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->grade) $q->where('gradeName',$request->grade);
                if($request->buildName) $q->where('buildName',$request->buildName);
                if($request->start_date) $q->where('created_at','>=',$request->start_date);
                if($request->end_date) $q->where('created_at','<=',$request->end_date);
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->collegeName) $q->where('collegeName',$request->collegeName);
                if($request->majorName) $q->where('majorName',$request->majorName);
                if($request->className) $q->where('className',$request->className);
            })
            ->orderBy('id','desc')
            ->paginate($pagesize);
        return showMsg('成功',200,$list);
    }

    /**
     * 导出住宿历史
     */
    public function export(Request $request){
        $header = [[
            'idnum'             =>    '学号',
            'username'          =>    '姓名',
            'sex'               =>    '性别',
            'buildName'         =>    '宿舍楼',
            'roomnum'           =>    '房间号',
            'bednum'            =>    '床位号',
            'collegeName'       =>    '学院',
            'majorName'         =>    '专业',
            'gradeName'         =>    '年级',
            'created_at'        =>    '入住时间',
            'updated_at'        =>    '退宿时间'
        ]];
        $data = DormitoryStayrecords::where(function ($q) use ($request){
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->grade) $q->where('gradeName',$request->grade);
                if($request->buildName) $q->where('buildName',$request->buildName);
                if($request->start_date) $q->where('created_at','>=',$request->start_date);
                if($request->end_date) $q->where('created_at','<=',$request->end_date);
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->collegeName) $q->where('collegeName',$request->collegeName);
                if($request->majorName) $q->where('majorName',$request->majorName);
                if($request->className) $q->where('className',$request->className);
            })
            ->get()->toArray();
        $excel = new Export($data, $header,'住宿历史');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }

    /**
     * 学生通行记录
     * @param type int 1学生2教师
     * @param username string 姓名
     * @param idnum string 学号
     * @param begin_time string 开始时间
     * @param end_time string 截止时间
     * @param pagesize int 每页数量
     * @param buildid int 楼宇id
     * @param college_name string 学院名称
     */
    public function student_access(Request $request){
        $type = $request->type ?? 1;
        $pagesize = $request->pageSize ?? 10;
        $idnum = auth()->user()->idnum;
        $buildids = $request->buildid ? [$request->buildid]: RedisGet('builds-'.$idnum);
        $list = DormitoryAccessRecord::whereIn('buildid',$buildids)
            ->whereType($type)
            //->whereStatus(0)
            ->where(function ($q) use ($request){
                if($request->begin_time) $q->where('pass_time','>=',$request->begin_time);
                if($request->end_time) $q->where('pass_time','<=',$request->end_time);
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->college_name) $q->where('college_name',$request->college_name);
            })
            ->orderBy('id','desc')
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

    /**
     * 正常通行记录导出
     * @param username string 姓名
     * @param idnum string 学号
     * @param begin_time string 开始时间
     * @param end_time string 截止时间
     */
    public function access_export(Request $request){
        $type = $request->type ?? 1;//1学员 2教职工
        $header = [[
            'idnum'             =>    $type==1 ? '学号' : '教职工',
            'username'          =>    '姓名',
            'sex'               =>    '性别',
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
        $idnum = auth()->user()->idnum;
        $buildids = $request->buildid ? [$request->buildid] : RedisGet('builds-'.$idnum);
        $data = DormitoryAccessRecord::whereIn('buildid',$buildids)
            ->whereType($type)
            ->whereStatus(0)
            ->where(function ($q) use ($request){
                if($request->begin_time) $q->where('pass_time','>=',$request->begin_time);
                if($request->end_time) $q->where('pass_time','<=',$request->end_time);
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->college_name) $q->where('college_name',$request->college_name);
            })
            ->get()
            ->toArray();
        $excel = new Export($data, $header,'通行记录');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }

    /**
     * 晚归记录
     * @paran  username  string 姓名
     * @param idnum string  学号
     * @param start_date 开始日期
     * @param end_date 开始日期
     * @param buildid int 楼宇
     * @param college_name string 学院名称
     */
    public function later(Request $request){
        $idnum = auth()->user()->idnum;
        $buildids = $request->buildid ? [$request->buildid]: RedisGet('builds-'.$idnum);
        $pagesize = $request->pageSize ?? 10;
        $list = DormitoryAccessRecord::whereType(1)
            ->whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->college_name) $q->where('college_name',$request->college_name);
                if($request->start_date) $q->where('pass_time','>=',$request->start_date);
                if($request->end_date) $q->where('pass_time','<=',$request->end_date.' 23:59:59');
            })
            ->whereStatus(1)
            ->orderBy('id','desc')
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

    /*
     * 晚归记录导出
     * @paran  username  string 姓名
     * @param idnum string  学号
     * @param start_date 开始日期
     * @param end_date 开始日期
     */
    public function later_export(Request $request){
        $start_date = $request->start_date ?? date('Y-m-d');
        $end_date = $request->end_date ?? date('Y-m-d 23:59:59');
        $idnum = auth()->user()->idnum;
        $buildids = $request->buildid ? [$request->buildid]: RedisGet('builds-'.$idnum);
        $data = DormitoryAccessRecord::whereType(1)
            ->whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->college_name) $q->where('college_name',$request->college_name);
            })
            ->whereStatus(1)
            ->whereBetween('pass_time',[$start_date,$end_date])
            ->get()
            ->toArray();
        $header = [[
            'idnum'             =>     '学号',
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
        $excel = new Export($data, $header,'晚归记录');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }

    /*
     * 未归记录
     * @paran  username  string 姓名
     * @param idnum string  学号
     * @param campusname string  校区
     * @param college_name string  学院
     * @param start_date 开始日期
     * @param end_date 开始日期
     * @param buildid 宿舍楼
     */
    public function noBack(Request $request){
        $pagesize = $request->pageSize ?? 10;
        $idnum = auth()->user()->idnum;
        $buildids = $request->buildid ? [$request->buildid]: RedisGet('builds-'.$idnum);
        $list = DormitoryNoBackRecord::whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->college_name) $q->where('college_name',$request->college_name);
                if($request->start_date && $request->end_date){
                    $q->whereBetween('date',[$request->start_date,$request->end_date]);
                }elseif($request->start_date){ //只存在开始日期
                    $q->where('date','>=',$request->start_date);
                }elseif($request->end_date){
                    $q->where('date','<=',$request->end_date);
                }
            })
            ->whereType(1)
            ->orderBy('id','desc')
            ->paginate($pagesize);
        //未归人数统计最近30天的数据
        if($request->start_date && $request->end_date){
            $begin_date =  $request->start_date;
            $end_date = $request->end_date;
        }elseif($request->start_date){
            $begin_date = $request->start_date;
            $end_date = date('Y-m-d');
        }elseif($request->end_date){
            $begin_date = date('Y-m-d',strtotime('-29 day',strtotime($request->end_date)));
            $end_date = $request->end_date;
        }else{
            $begin_date = date('Y-m-d', strtotime("-29 day"));
            $end_date = date('Y-m-d');
        }
        $limit_day = intval(round((strtotime($end_date)-strtotime($begin_date))/86400));
        $skip = 1; //默认间隔1天
        if($limit_day>30){ //相差》30天
            $skip = intval(round($limit_day/30));//间隔天数
        }
        $max = intval(round($limit_day/$skip));
        $dates = $value = [];
        $j = 0;
        for($i=$max;$i>=0;$i--){
            $tap = $skip*$i;
            $date=date('Y-m-d', strtotime("-$tap day",strtotime($end_date)));
            $key=date('m/d', strtotime("-$tap day",strtotime($end_date)));
            $dates[$j] = $key;
            $value[$j] = DormitoryNoBackRecord::where('date',$date)->where('type',1)
                ->where(function ($q) use ($request){
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->college_name) $q->where('college_name',$request->college_name);
            })->whereIn('buildid',$buildids)->count();
            $j++;
        }
        return showMsg('获取成功',200,$list,['date'=>$dates,'value'=>$value]);
    }

    /**
     * 未归记录导出
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @param username  string 姓名
     * @param idnum string  学号
     * @param start_date 开始日期
     * @param end_date 开始日期
     */
    public function no_back_export(Request $request){
//        $start_date = $request->start_date ?? date('Y-m-d');
//        $end_date = $request->end_date ?? date('Y-m-d');
        $idnum = auth()->user()->idnum;
        $buildids = $request->buildid ? [$request->buildid]: RedisGet('builds-'.$idnum);
        $data = DormitoryNoBackRecord::whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->college_name) $q->where('college_name',$request->college_name);
                if($request->start_date) $q->where('date','>=',$request['start_date']);
                if($request->end_date) $q->where('date','<=',$request['end_date']." 23:59:59");
            })
         //   ->whereBetween('date',[$start_date,$end_date])
            ->whereType(1)
            ->orderBy('date','desc')
            ->get()
            ->toArray();
        $header = [[
            'idnum'             =>     '学号',
            'username'          =>    '姓名',
            'sex'               =>    '性别',
            'college_name'       =>    '学院',
            'major_name'         =>    '专业',
            'grade_name'         =>    '年级',
            'class_name'         =>    '班级',
            'build_name'        =>    '宿舍楼',
            'roomnum'          =>     '房间',
            'bednum'            =>     '床位',
            'date'              =>      '未归日期'
        ]];
        $excel = new Export($data, $header,'未归记录');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }

    /**
     * 多日无记录人员
     * @param username  string 姓名
     * @param idnum string  学号
     * @param start_date 开始日期
     * @param end_date 开始日期
     * @param campusname 校区
     * @param college_name  学院
     */
    public function noRecord(Request $request){
        $pagesize = $request->pageSize ?? 10;
        $idnum = auth()->user()->idnum;

        $buildids = $request->buildid ? [$request->buildid]: RedisGet('builds-'.$idnum);
        $list = DormitoryNoRecord::whereType(1)
            ->where(function ($q) use ($request,$buildids){
                if($buildids) $q->whereIn('buildid',$buildids);
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->college_name) $q->where('college_name',$request->college_name);
                if($request->start_date) $q->where('begin_date','>=',$request->start_date);
                if($request->end_date) $q->where('end_date','<=',$request->end_date);
            })
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

    /**
     * 多日无记录导出
     */
    public function no_record_export(Request $request){
        $start_date = $request->start_date ?? date('Y-m-d');
        $end_date = $request->end_date ?? date('Y-m-d');
        $idnum = auth()->user()->idnum;
        $buildids = $request->buildid ? [$request->buildid]: RedisGet('builds-'.$idnum);
        $data = DormitoryNoRecord::whereIn('buildid',$buildids)
            ->where(function ($q) use ($request){
                if($request->username) $q->where('username','like','%'.$request->username.'%');
                if($request->idnum) $q->whereIdnum($request->idnum);
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->college_name) $q->where('college_name',$request->college_name);
            })
            ->where('begin_date','<=',$start_date)
            ->where('end_date','>=',$end_date)
            ->whereType(1)
            ->get()
            ->toArray();
        $header = [[
            'idnum'             =>     '学号',
            'username'          =>    '姓名',
            'sex'               =>    '性别',
            'college_name'       =>    '学院',
            'major_name'         =>    '专业',
            'grade_name'         =>    '年级',
            'class_name'         =>    '班级',
            'build_name'        =>    '宿舍楼',
            'roomnum'          =>     '房间',
            'bednum'            =>     '床位',
            'date'              =>      '未归日期'
        ]];
        $excel = new Export($data, $header,'多日无记录');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }

    /**
     * 陌生人识别记录
     * @param buildid 宿舍楼
     * @param start_date 开始日期
     * @param end_date 开始日期
     */
    public function strange(Request $request){
        $pagesize = $request->pageSize ?? 10;
        $list = DormitoryStrangeAccessRecord::where(function ($q) use ($request){
                if($request->campusname) $q->where('campusname',$request->campusname);
                if($request->buildid) $q->where('buildid',$request->buildid);
                if($request->start_date) $q->where('pass_time','>=',$request->start_date);
                if($request->end_date) $q->where('pass_time','<=',$request->end_date.' 23:59:59');
            })
            ->orderBy('id','desc')
            ->paginate($pagesize);
        return showMsg('成功',200,$list);
    }

    /**
     * 导出陌生人记录
     * @param Request $request
     */
    public function strangeExport(Request $request){

        $list = DormitoryStrangeAccessRecord::where(function ($q) use ($request){
            if($request->campusname) $q->where('campusname',$request->campusname);
            if($request->buildid) $q->where('buildid',$request->buildid);
            if($request->start_date) $q->where('pass_time','>=',$request->start_date);
            if($request->end_date) $q->where('pass_time','>=',$request->end_date.' 23:59:59');
        })->orderBy('id','desc')
            ->get()
            ->toArray();

        $header = [[
            'campusname'        =>  '校区',
            'pass_location'     =>  '识别地点',
            'pass_way'          =>  '识别通道',
            'direction'         =>  '识别状态',
            'analyse'           =>  '对比分析值',
            'pass_time'         =>  '识别时间',
        ]];

        $excel = new Export($list, $header,'陌生人记录');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }
}
