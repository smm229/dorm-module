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
'DormPermission' => \Modules\Dorm\Http\Middleware\DormPermission::class
```

