<?php
namespace Modules\Dorm\Http\Controllers;

use App\Exports\Export;
use App\Http\Controllers\Controller;
use App\Traits\SerializeDate;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Modules\Dorm\Entities\DormitoryWarningRecord;


class WarningRecordController extends Controller{
    use SerializeDate;
    use Helpers;

    /**
     * 首页列表
     * @param Request $request
     */
    public function lists(Request $request){

        $list = DormitoryWarningRecord::where(function ($q) use ($request){
            if($request['campusname']) $q->where('campusname', $request['campusname']);
            if($request['buildid']) $q->where('buildid', $request['buildid']);
            if($request['start_date']) $q->where('pass_time','>=',$request['start_date']);
            if($request['end_date']) $q->where('pass_time','<=',$request['end_date']." 23:59:59");
        })->orderBy('id','desc')->paginate($request['pageSize']);
        return showMsg('成功', 200,$list);

    }

    /**
     * 非法通行记录获取视频
     * @param  id 记录id
     */
    public function getVideo(Request $request){
        if(!$request->id){
            return showMsg('缺少参数');
        }
        $record = DormitoryWarningRecord::find($request->id);
        if(!$record){
            return showMsg('数据查找失败');
        }
        if($record->video){
            return showMsg('成功',200,['video'=>$record->video]);
        }
        if(!$record->start_time){
            return showMsg('未发现相关视频');
        }
        $filename = '';
        $i=0;
        try{
            START:
            $new_video_path = "/files/" . date('Ymd') . "/".create_secret(10).'.mp4';
            //查找对应的视频
            $videos = readNebulaDir(public_path($record->path),3);
            if(!$videos){
                throw new \Exception('未发现相关视频');
            }
            foreach($videos as $v){
                if(strpos($v,$record->start_time.'-')!==false){  //存在的
                    $filename = $v;
                    break;
                }
            }
            if(!$filename){ //不存在准确时间，查找相似时间内的数据,待测试
                foreach($videos as $v){
                    $arr = explode('-',$v);
                    $arr2 = explode('[A]',$arr[1]);
                    if($record->start_time>$arr[0] && $record->start_time<$arr2[0]){ //在时间内
                        $filename = $v;
                        break;
                    }
                }
            }
            if(!$filename){
                throw new \Exception('未发现相关视频');
            }
            if(strpos($filename,'.dav_')!==false){ //未生成
                return showMsg('视频生成中，请稍后再试');
            }
            //转码视频
            $str = "ffmpeg -i " . public_path($record->path."/".$filename) . " -vcodec copy " . public_path('/uploads' . $new_video_path);
            file_put_contents(storage_path('logs/davtomp4.log'),date('Y-m-d H:i:s').'转转过程：'.$str.PHP_EOL,FILE_APPEND);
            exec($str);
            if(file_exists(public_path('/uploads' . $new_video_path))){
                $record->video = env('APP_URL').'/uploads'.$new_video_path;
                $record->save();
                return showMsg('成功',200,['video'=>env('APP_URL').'/uploads'.$new_video_path]);
            }else{
                return showMsg('视频生成中，请稍后再试');
            }
        }catch(\Exception $e){
            $record->start_time = null;
            $record->save();
            return showMsg($e->getMessage());
        }

    }

    /**
     * 非法通行导出
     * @param Request $request
     */
    public function export(Request $request){

        $list = DormitoryWarningRecord::where(function ($q) use ($request){
            if($request['campusname']) $q->where('campusname', $request['campusname']);
            if($request['buildid']) $q->where('buildid', $request['buildid']);
            if($request['start_date']) $q->where('pass_time','>=',$request['start_date']);
            if($request['end_date']) $q->where('pass_time','<=',$request['end_date']." 23:59:59");
        })->orderBy('id','desc')
            ->get()
            ->toArray();

        $header = [[
            'pass_time'        =>  '通行时间',
            'campusname'       =>  '校区',
            'pass_location'    =>  '识别地点',
            'pass_way'         =>  '识别通道',
            'direction_name'   =>  '方向',
            'type'             =>  '事件',
        ]];
        $excel = new Export($list, $header,'非法通行');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }
}
