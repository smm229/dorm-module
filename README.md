# dorm-module
laravel8模块化-宿舍模块
项目私有化使用
请先安装 nwidart/laravel-modules和joshbrw/laravel-module-installer

1、composer require smm229/dorm-module
2、在Kernel.php添加路由中间件
'DormPermission' => \Modules\Dorm\Http\Middleware\DormPermission::class

