# dorm-module

laravel8模块化-宿舍模块<br/>
项目私有化使用<br/>
请先安装 [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules) 和[joshbrw/laravel-module-installer](https://github.com/joshbrw/laravel-module-installer)

1、引入包
```
composer require smm229/dorm-module
```
2、在Kernel.php添加路由中间件<br/>
```
'DormPermission' => \Modules\Dorm\Http\Middleware\DormPermission::class //模块权限
'AuthDel' => \Modules\Dorm\Http\Middleware\AuthDel::class //验证角色权限
```
3、编辑config/auth.php
```
'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'jwt',  // 默认是 token
            'provider' => 'users',
        ],
        // 新增dorm 模块
        'dorm' => [
            'driver' => 'jwt',
            'provider' => 'dorms',
        ]
    ],
    ......
    
    'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    //dorm模块
    'dorms' => [
          'driver' => 'eloquent',
          'model' => \Modules\Dorm\Entities\DormitoryUsers::class,
      ]


```
4、在app\Console\Kernel.php 添加计划任务,添加以下代码<br/>
```
    protected $commands = [
        //昨日未归
        \Modules\Dorm\Console\NoBack::class,
        //截止昨日多日无记录
        \Modules\Dorm\Console\NoRecord::class,
        //宿管首页数据统计告警记录
        \Modules\Dorm\Console\RecordInfo::class,
        //宿管首页统计24小时通行数
        \Modules\Dorm\Console\Reckon::class,
    ];
    .....
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('no_back')->dailyAt("00:01");//第一分钟执行
         $schedule->command('no_record')->dailyAt("01:00");//凌晨一点执行
         $schedule->command('record_info')->everyTenMinutes();//10分钟执行
         $schedule->command('reckon')->everyTenMinutes();//10分钟执行
    }
```
5、添加crontab计划任务<br/>
```
    * * * * * php /home/www/项目路径/artisan schedule:run >> /dev/null 2>&1
```

6、开启守护进程监听队列
```
守护进程
php artisan queue:work --daemon &
或者
nohup php artisan queue:listen >/dev/null 2>&1 &
或者（我用的这个）
nohup php artisan queue:work --daemon >/dev/null 2>&1 &
刷新队列
php artisan queue:flush
重新运行队列
php artisan queue:restart
查看进程数量：
ps -ef | grep 'artisan queue' |grep -v 'grep' | wc -l
查看详细进程
ps -ef | grep 'artisan queue'
```
7、计划任务执行进程重启判断脚本（暂未启用）：
nohup /home/wwwroot/api.hnrtxx_dev/job.sh >/tmp/job.log 2>&1 &
