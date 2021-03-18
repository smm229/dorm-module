<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDormitoryRepairTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_repair', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('buildid')->index('idx_bid')->comment('楼id');
            $table->Integer('floornum')->comment('楼层');
            $table->string('orderno', 50)->comment('工单号');
            $table->string('roomnum', 50)->comment('房间号');
            $table->string('type')->comment('故障类型');
            $table->string('intro')->nullable()->comment('故障详情');
            $table->string('idnum')->comment('学员编号');
            $table->string('username', 50)->comment('姓名');
            $table->dateTime('time')->comment('期望维修时间');
            $table->string('phone',20)->comment('手机号');
            $table->text('covers')->nullable()->comment('图片');
            $table->string('teacher_idnum')->nullable()->comment('维修工编号');
            $table->dateTime('take_time')->nullable()->comment('接单时间');
            $table->dateTime('begin_time')->nullable()->comment('开始维修时间');
            $table->dateTime('end_time')->nullable()->comment('结束维修时间');
            $table->tinyInteger('status')->default(1)->comment('状态1待接单2已接单3维修中4已完成');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_repair` comment '宿舍报修记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_repair');
    }
}
