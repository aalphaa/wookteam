export default {
    methods: {
        outProject(projectid, successCallback) {
            this.$Modal.confirm({
                title: '退出项目',
                content: '你确定要退出此项目吗？',
                loading: true,
                onOk: () => {
                    $A.aAjax({
                        url: 'project/out?projectid=' + projectid,
                        error: () => {
                            this.$Modal.remove();
                            this.$Message.error(this.$L('网络繁忙，请稍后再试！'));
                        },
                        success: (res) => {
                            this.$Modal.remove();
                            setTimeout(() => {
                                if (res.ret === 1) {
                                    this.$Message.success(res.msg);
                                    typeof successCallback === "function" && successCallback();
                                }else{
                                    this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                }
                            }, 350);
                        }
                    });
                }
            });
        },

        favorProject(act, projectid, successCallback) {
            $A.aAjax({
                url: 'project/favor',
                data: {
                    act: act,
                    projectid: projectid,
                },
                error: () => {
                    this.$Modal.remove();
                    this.$Message.error(this.$L('网络繁忙，请稍后再试！'));
                },
                success: (res) => {
                    this.$Modal.remove();
                    setTimeout(() => {
                        if (res.ret === 1) {
                            this.$Message.success(res.msg);
                            typeof successCallback === "function" && successCallback();
                        }else{
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                        }
                    }, 350);
                }
            });
        },

        deleteProject(projectid, successCallback) {
            this.$Modal.confirm({
                title: '删除项目',
                content: '你确定要删除此项目吗？',
                loading: true,
                onOk: () => {
                    $A.aAjax({
                        url: 'project/delete?projectid=' + projectid,
                        error: () => {
                            this.$Modal.remove();
                            this.$Message.error(this.$L('网络繁忙，请稍后再试！'));
                        },
                        success: (res) => {
                            this.$Modal.remove();
                            setTimeout(() => {
                                if (res.ret === 1) {
                                    this.$Message.success(res.msg);
                                    typeof successCallback === "function" && successCallback();
                                }else{
                                    this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                }
                            }, 350);
                        }
                    });
                }
            });
        }
    }
}
