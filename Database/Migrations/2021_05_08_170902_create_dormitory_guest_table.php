<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryGuestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_guest', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 255)->comment('访客姓名');
            $table->string('headimg', 255)->comment('头像');
            $table->tinyInteger('sex')->comment('访客性别');
            $table->dateTime('begin_time')->comment('人脸有效期开始');
            $table->dateTime('end_time')->comment('人脸有效期截止');
            $table->string('visit_place', 255)->comment('来访地点，组或者楼的id，多个用逗号分隔');
            $table->integer('receptionUserId')->comment('受访人的linkid');
            $table->integer('link_id');
            $table->string('mobile', 20)->nullable()->comment('访客手机');
            $table->string('ID_number', 20)->nullable()->comment('访客身份证号');
            $table->string('visit_note', 255)->nullable()->comment('来访目的');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_guest` comment '访客'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_guest');
    }
}
