export default {
    methods: {
        taskComplete(taskDetail, complete = null) {
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
                        $A.triggerTaskInfoListener(complete ? 'complete' : 'unfinished', res.data);
                        $A.triggerTaskInfoChange(taskDetail.id);
                    } else {
                        this.$set(taskDetail, 'complete', !complete);
                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                    }
                }
            });
        },

        renderTaskTitle(h, params) {
            return h('div', [
                h('Icon', {
                    props: { type: params.row.complete ? 'md-checkbox-outline' : 'md-square-outline' },
                    style: {marginRight: '4px', cursor: 'pointer', fontSize: '15px'},
                    on: {
                        click: () => {
                            this.taskComplete(params.row, !params.row.complete);
                        }
                    }
                }),
                h('span', {
                    style: {cursor: 'pointer', textDecoration: params.row.complete ? 'line-through' : ''},
                    on: {
                        click: () => {
                            this.taskDetail(params.row);
                        }
                    }
                }, params.row.title)
            ]);
        }
    }
}
