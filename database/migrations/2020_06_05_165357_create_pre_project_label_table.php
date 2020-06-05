<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreProjectLabelTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('project_label', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('projectid')->nullable()->default(0)->comment('项目ID');
			$table->string('title', 100)->nullable()->default('')->comment('分类名称');
			$table->integer('inorder')->nullable()->comment('排序(ASC)');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('project_label');
	}
}
