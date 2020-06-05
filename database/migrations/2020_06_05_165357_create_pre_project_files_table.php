<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreProjectFilesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('project_files', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('projectid')->nullable()->default(0)->comment('项目ID');
			$table->integer('taskid')->nullable()->default(0)->comment('任务ID');
			$table->string('name', 100)->nullable()->default('')->comment('文件名称');
			$table->integer('size')->nullable()->default(0)->comment('文件大小(B)');
			$table->string('ext', 20)->nullable()->default('')->comment('文件格式');
			$table->string('path')->nullable()->default('')->comment('文件地址');
			$table->string('thumb')->nullable()->default('')->comment('缩略图');
			$table->string('username')->nullable()->default('')->comment('上传用户');
			$table->integer('download')->nullable()->default(0)->comment('下载次数');
			$table->boolean('delete')->nullable()->default(0)->comment('是否删除');
			$table->bigInteger('deletedate')->nullable()->default(0)->comment('删除时间');
			$table->bigInteger('indate')->nullable()->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('project_files');
	}
}
