<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_dorm')->create('dormitory_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('idnum', 32)->unique('username')->comment('教师工号');
            $table->string('password')->comment('密码');
            $table->string('username')->comment('教师姓名');
            $table->boolean('sex')->nullable()->default(1)->comment('性别 1 男 2 女 3 保密');
            $table->string('email', 36)->nullable()->default('')->comment('邮箱');
            $table->string('mobile', 20)->nullable()->default('')->comment('手机号');
            $table->rememberToken()->comment('jwt的token');
            $table->string('headimg')->nullable()->default('')->comment('头像');
            $table->tinyInteger('disable')->nullable()->default(0)->comment('1 禁用 0 启用');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->softDeletes()->comment('删除时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::connection('mysql_dorm')->statement("ALTER TABLE `dormitory_users` comment '管理员表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_dorm')->drop('dormitory_users', function (Blueprint $table) {












        });
    }
}
