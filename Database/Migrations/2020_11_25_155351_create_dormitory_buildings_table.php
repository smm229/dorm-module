<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryBuildingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_dorm')->create('dormitory_buildings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->default('')->comment('楼名称');
            $table->Integer('buildtype')->comment('楼的类型');
            $table->tinyInteger('floor')->comment('楼的总层数');
            $table->string('ename', 100)->nullable()->default('')->comment('楼英文');
            $table->text('icon')->nullable()->comment('楼图像信息');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::connection('mysql_dorm')->statement("ALTER TABLE `dormitory_buildings` comment '楼宇表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_dorm')->drop('dormitory_buildings', function (Blueprint $table) {








        });
    }
}
