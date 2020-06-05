<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreProjectTaskTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('project_task', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('projectid')->nullable()->default(0)->index('IDEX_projectid')->comment('项目ID');
			$table->integer('labelid')->nullable()->default(0)->comment('项目子分类ID');
			$table->string('createuser', 100)->nullable()->default('')->comment('创建者用户名');
			$table->string('username', 100)->nullable()->default('')->comment('负责人用户名');
			$table->string('title')->nullable()->default('')->comment('标题');
			$table->string('desc', 500)->nullable()->default('')->comment('描述');
			$table->boolean('level')->nullable()->default(1)->comment('优先级别：1~4');
			$table->boolean('complete')->nullable()->default(0)->index('IDEX_status')->comment('是否完成：0|1');
			$table->bigInteger('completedate')->nullable()->default(0)->comment('完成时间');
			$table->text('subtask')->nullable()->comment('子任务列表');
			$table->text('follower')->nullable()->comment('关注人列表');
			$table->integer('pushlid')->nullable()->default(0)->comment('已发送的最后动态ID');
			$table->integer('filenum')->nullable()->default(0)->comment('附件数量');
			$table->bigInteger('startdate')->nullable()->default(0)->comment('计划开始时间');
			$table->bigInteger('enddate')->nullable()->default(0)->comment('计划结束时间');
			$table->tinyInteger('archived')->nullable()->default(0)->comment('是否归档');
			$table->bigInteger('archiveddate')->nullable()->default(0)->comment('归档时间');
			$table->boolean('delete')->nullable()->default(0)->index('IDEX_delete')->comment('是否删除');
			$table->bigInteger('deletedate')->nullable()->default(0)->comment('删除时间');
			$table->integer('inorder')->nullable()->default(0)->comment('排序(DESC)');
			$table->integer('userorder')->nullable()->default(0)->comment('会员自己的排序(DESC)');
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
		Schema::drop('project_task');
	}
}
