export default {
    methods: {
        taskComplete(taskDetail, complete, callback = null) {
            if (taskDetail['loadIng'] === true) {
                return;
            }
            this.$set(taskDetail, 'loadIng', true);
            this.$set(taskDetail, 'complete', !!complete);
            $A.aAjax({
                url: 'project/task/edit',
                data: {
                    act: complete ? 'complete' : 'unfinished',
                    taskid: taskDetail.id,
                },
                complete: () => {
                    this.$set(taskDetail, 'loadIng', false);
                },
                error: () => {
                    this.$set(taskDetail, 'complete', !complete);
                    alert(this.$L('网络繁忙，请稍后再试！'));
                },
                success: (res) => {
                    if (res.ret === 1) {
                        this.$Message.success(res.msg);
                        typeof callback === "function" && callback(res.date);
                    } else {
                        this.$set(taskDetail, 'complete', !complete);
                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                    }
                }
            });
        },
    }
}
