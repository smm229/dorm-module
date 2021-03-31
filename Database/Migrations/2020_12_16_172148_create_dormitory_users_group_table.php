<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDormitoryUsersGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_users_group', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型1学生2老师3访客4黑名单');
            $table->integer('senselink_id')->comment('senselink用户id');
            $table->integer('groupid')->comment('senselink组id');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_users_group` comment '人员用户组关系表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_users_group');
    }
}
