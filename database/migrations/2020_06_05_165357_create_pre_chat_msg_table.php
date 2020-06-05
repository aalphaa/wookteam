<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreChatMsgTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('chat_msg', function(Blueprint $table)
		{
			$table->bigIncrements('id');
			$table->integer('did')->nullable()->default(0)->index('IDEX_did')->comment('对话ID');
			$table->string('username', 100)->nullable()->default('')->index('IDEX_username')->comment('发送者');
			$table->string('receive', 100)->nullable()->default('')->index('IDEX_receive')->comment('接受者');
			$table->text('message')->nullable()->comment('详细内容');
			$table->boolean('roger')->nullable()->default(0)->comment('是否已读');
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
		Schema::drop('chat_msg');
	}
}
