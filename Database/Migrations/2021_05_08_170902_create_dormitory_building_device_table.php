<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryBuildingDeviceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_building_device', function (Blueprint $table) {
            $table->increments('id');
            $table->string('senselink_sn')->comment('senselink设备号');
            $table->integer('groupid')->comment('senselink组id');
            $table->integer('deviceid')->comment('设备id');
            $table->string('devicename', 255)->comment('设备名字');
            $table->tinyInteger('grouptype')->comment('组类型 1 员工组 2 访客组 3 黑名单组');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_building_device` comment '组关联设备'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_building_device');
    }
}
