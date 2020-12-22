<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_category', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fid')->default(0)->index('fid')->comment('上级分类id');
            $table->string('name', 100)->default('')->comment('分类名称');
            $table->string('ckey', 100)->default('')->index('ckey')->comment('分类标识');
            $table->integer('sort')->default(1)->comment('排序');
            $table->string('ename', 100)->nullable()->default('')->comment('分类英文名');
            $table->text('icon')->nullable()->comment('图标');
            $table->string('describ', 100)->nullable()->default('')->comment('类型描述');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_category` comment '通用分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dormitory_category', function (Blueprint $table) {










        });
    }
}
