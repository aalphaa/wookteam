<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreChatDialogTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('chat_dialog', function(Blueprint $table)
		{
			$table->bigIncrements('id');
			$table->string('user1', 100)->nullable()->default('')->index('IDEX_user1')->comment('用户名1（发起对话者）');
			$table->string('user2', 100)->nullable()->default('')->index('IDEX_user2')->comment('用户名2');
			$table->integer('unread1')->nullable()->default(0)->comment('用户1未读信息数');
			$table->integer('unread2')->nullable()->default(0)->comment('用户2未读信息数');
			$table->boolean('del1')->nullable()->default(0)->comment('用户1删除');
			$table->boolean('del2')->nullable()->default(0)->comment('用户2删除');
			$table->bigInteger('lastid1')->nullable()->default(0)->comment('用户1删除到的聊天ID');
			$table->bigInteger('lastid2')->nullable()->default(0)->comment('用户2删除到的聊天ID');
			$table->text('lasttext')->nullable()->comment('最后消息');
			$table->bigInteger('lastdate')->nullable()->default(0)->comment('最后消息时间');
			$table->bigInteger('indate')->nullable()->default(0)->comment('数据生成时间');
			$table->unique(['user1','user2'], 'IDEX_user1_user2');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('chat_dialog');
	}
}
