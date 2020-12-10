<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryBedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_dorm')->create('dormitory_beds', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('buildid')->comment('楼宇id');
            $table->Integer('roomid')->comment('房间id');
            $table->string('bednum')->comment('床位号');
            $table->string('idnum')->nullable()->comment('床位的所属人编号');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::connection('mysql_dorm')->statement("ALTER TABLE `dormitory_beds` comment '床位表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_dorm')->drop('dormitory_beds', function (Blueprint $table) {






        });
    }
}
