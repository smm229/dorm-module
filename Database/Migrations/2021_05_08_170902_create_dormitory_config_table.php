<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->unique()->comment('键名');
            $table->string('value')->comment('键值');
            $table->text('content')->comment('描述');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_config` comment '宿管模块配置'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_config');
    }
}
