<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreProjectUsersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('project_users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('type', 50)->nullable()->default('')->index('IDEX_type')->comment('类型：成员、收藏、关注');
			$table->integer('projectid')->nullable()->default(0)->index('IDEX_projectid')->comment('项目ID');
			$table->integer('taskid')->nullable()->default(0)->index('IDEX_taskid')->comment('任务ID');
			$table->boolean('isowner')->nullable()->default(0)->comment('是否项目所有者');
			$table->string('username', 100)->nullable()->default('')->index('IDEX_username')->comment('关系用户名');
			$table->bigInteger('indate')->nullable()->default(0)->comment('添加时间');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('project_users');
	}
}
