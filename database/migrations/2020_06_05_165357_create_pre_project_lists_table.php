<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreProjectListsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('project_lists', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title')->nullable()->default('')->comment('项目名称');
			$table->string('createuser', 100)->nullable()->default('')->comment('项目创建者用户名');
			$table->string('username', 100)->nullable()->default('')->comment('项目所有者用户名');
			$table->integer('complete')->nullable()->default(0)->comment('已完成数量');
			$table->integer('unfinished')->nullable()->default(0)->comment('未完成数量');
			$table->bigInteger('indate')->nullable()->default(0)->comment('添加时间');
			$table->boolean('delete')->nullable()->default(0)->index('IDEX_delete')->comment('是否删除');
			$table->bigInteger('deletedate')->nullable()->default(0)->comment('删除时间');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('project_lists');
	}
}
