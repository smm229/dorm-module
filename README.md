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
'DormPermission' => \Modules\Dorm\Http\Middleware\DormPermission::class //验证权限
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

