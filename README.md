# dorm-module

laravel8模块化-宿舍模块<br/>
项目私有化使用<br/>
请先安装 nwidart/laravel-modules和joshbrw/laravel-module-installer<br/>

1、
```
composer require smm229/dorm-module

2、在Kernel.php添加路由中间件<br/>
```
'DormPermission' => \Modules\Dorm\Http\Middleware\DormPermission::class

