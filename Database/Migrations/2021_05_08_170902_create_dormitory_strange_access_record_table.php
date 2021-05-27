<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryStrangeAccessRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_strange_access_record', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('buildid')->comment('楼id');
            $table->dateTime('pass_time')->comment('通行时间');
            $table->string('pass_location')->nullable()->comment('通行地点');
            $table->string('pass_way', 50)->nullable()->comment('通道');
            $table->string('cover')->nullable()->comment('识别图片');
            $table->string('direction', 50)->nullable()->comment('方向');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_strange_access_record` comment '陌生人通行记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_strange_access_record');
    }
}
