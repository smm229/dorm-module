<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryUsersBuildingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_dorm')->create('dormitory_users_building', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('idnum', 32)->index('username')->comment('教师工号');
            $table->Integer('buildid')->comment('楼宇id');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->softDeletes()->comment('删除时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::connection('mysql_dorm')->statement("ALTER TABLE `dormitory_users_building` comment '管理员宿舍表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_dorm')->drop('dormitory_users_building', function (Blueprint $table) {






        });
    }
}
