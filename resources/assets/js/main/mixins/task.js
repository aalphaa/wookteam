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
                        typeof callback === "function" && callback(res.data);
                    } else {
                        this.$set(taskDetail, 'complete', !complete);
                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                    }
                }
            });
        },

        renderTaskTitle(h, params, callback) {
            let taskDetail = params.row;
            return h('div', [
                h('Icon', {
                    props: { type: params.row.complete ? 'md-checkbox-outline' : 'md-square-outline' },
                    style: {marginRight: '4px', cursor: 'pointer', fontSize: '15px'},
                    on: {
                        click: () => {
                            this.taskComplete(params.row, !params.row.complete, (detail) => {
                                this.$emit("change", detail.complete ? 'complete' : 'unfinished', detail);
                                typeof callback === "function" && callback(detail.complete ? 'complete' : 'unfinished', detail);
                            });
                        }
                    }
                }),
                h('span', {
                    style: {cursor: 'pointer'},
                    on: {
                        click: () => {
                            this.taskDetail(taskDetail, (act, detail) => {
                                for (let key in detail) {
                                    if (detail.hasOwnProperty(key)) {
                                        this.$set(taskDetail, key, detail[key])
                                    }
                                }
                                //
                                if (typeof callback === "function") {
                                    callback(act, detail);
                                } else {
                                    switch (act) {
                                        case "username":    // 负责人
                                        case "delete":      // 删除任务
                                        case "archived":    // 归档
                                            this.lists.some((task, i) => {
                                                if (task.id == detail.id) {
                                                    this.lists.splice(i, 1);
                                                    return true;
                                                }
                                            });
                                            break;

                                        case "unarchived":  // 取消归档
                                            let has = false;
                                            this.lists.some((task) => {
                                                if (task.id == detail.id) {
                                                    return has = true;
                                                }
                                            });
                                            if (!has) {
                                                this.lists.unshift(detail);
                                            }
                                            break;
                                    }
                                }
                                //
                                this.$emit("change", act, detail);
                            });
                        }
                    }
                }, taskDetail.title)
            ]);
        }
    }
}
