<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryGuestAccessRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_guest_access_record', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('buildid')->comment('楼id');
            $table->dateTime('pass_time')->comment('通行时间');
            $table->string('pass_location')->nullable()->comment('通行地点');
            $table->string('pass_way', 50)->nullable()->comment('通道');
            $table->string('cover')->nullable()->comment('识别图片');
            $table->string('truename', 50)->nullable()->comment('来访者姓名');
            $table->string('mobile', 11)->nullable()->comment('手机号');
            $table->string('ID_number', 20)->nullable()->comment('身份证号码');
            $table->string('sex',10)->comment('性别');
            $table->string('direction', 50)->nullable()->comment('方向');
            $table->string('note')->nullable()->comment('来访目的');
            $table->string('visit')->nullable()->comment('来访地点');
            $table->string('touser')->nullable()->comment('受访人');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_guest_access_record` comment '访客记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dormitory_guest_access_record', function (Blueprint $table) {
            

            
        });
    }
}
