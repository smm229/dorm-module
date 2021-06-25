<?php

namespace Modules\Dorm\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryWarningRecord;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DavToVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dav_to_video';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '转换视频';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        set_time_limit(0);
        $date = date('Y-m-d');//date('Y-m-d',strtotime('-1 day'));//昨天
        $list = DormitoryWarningRecord::where('date',$date)
            ->where('video','')
            ->whereNotNull('start_time')
            ->get();
        if($list->first()) {
            foreach($list as $record) {
                $filename = '';
                try{
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
                    if(!$filename){
                        throw new \Exception('未发现相关视频');
                    }
                    //转码视频 ffmpeg的安装路径
                    $str = "/usr/local/bin/ffmpeg -i " . public_path($record->path."/".$filename) . " -vcodec copy " . public_path('/uploads' . $new_video_path);
                    file_put_contents(storage_path('logs/davtomp4.log'),date('Y-m-d H:i:s').'转转过程：'.$str.PHP_EOL,FILE_APPEND);
                    exec($str,$r);
                    if(file_exists(public_path('/uploads' . $new_video_path))){
                        DormitoryWarningRecord::whereId($record->id)->update(['video'=>env('APP_URL').'/uploads'.$new_video_path]);
                    }else {
                        throw new \Exception(json_encode($r));
                    }
                }catch(\Exception $e){
                    DormitoryWarningRecord::whereId($record->id)->update(['start_time'=>null]);
                    file_put_contents(storage_path('logs/davtomp4.log'),date('Y-m-d H:i:s').'转转失败：'.$e->getMessage().PHP_EOL,FILE_APPEND);
                }
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
