<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_group', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->default('')->comment('楼名称');
            $table->tinyInteger('type')->default(1)->comment('楼宇类型1楼宇2其他');
            $table->integer('groupid')->comment('senselink员工组id');
            $table->integer('visitor_groupid')->comment('senselink访客组id');
            $table->integer('blacklist_groupid')->comment('senselink黑名单组id');
            $table->integer('buildtype')->nullable()->comment('楼分类');
            $table->tinyInteger('floor')->nullable()->comment('楼的总层数');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_group` comment '用户组'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_group');
    }
}
