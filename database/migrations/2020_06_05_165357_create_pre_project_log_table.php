<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreProjectLogTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('project_log', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('type', 50)->nullable()->default('')->comment('类型：评论、日志');
			$table->integer('projectid')->nullable()->default(0)->comment('项目ID');
			$table->integer('taskid')->nullable()->default(0)->comment('相关数据ID');
			$table->string('username', 100)->nullable()->default('')->comment('关系用户名');
			$table->string('detail', 500)->nullable()->default('')->comment('详细信息');
			$table->bigInteger('indate')->nullable()->default(0)->comment('添加时间');
			$table->text('other')->nullable()->comment('其他参数');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('project_log');
	}
}
