<template>
    <drawer-tabs-container>
        <div class="project-complete">
            <!-- 按钮 -->
            <Button :loading="loadIng > 0" type="primary" icon="md-add" @click="addUser">添加成员</Button>
            <!-- 列表 -->
            <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
            <!-- 分页 -->
            <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>
        </div>
    </drawer-tabs-container>
</template>

<style lang="scss" scoped>
    .project-complete {
        padding: 0 12px;
        .tableFill {
            margin: 12px 0 20px;
        }
    }
</style>
<script>
    import DrawerTabsContainer from "../DrawerTabsContainer";
    export default {
        name: 'ProjectUsers',
        components: {DrawerTabsContainer},
        props: {
            projectid: {
                default: 0
            },
            canload: {
                type: Boolean,
                default: true
            },
        },
        data () {
            return {
                loadYet: false,

                loadIng: 0,

                columns: [],

                lists: [],
                listPage: 1,
                listTotal: 0,
                noDataText: "数据加载中.....",
            }
        },
        created() {
            this.columns = [{
                "title": "头像",
                "minWidth": 60,
                "maxWidth": 100,
                render: (h, params) => {
                    return h('img', {
                        style: {
                            width: "32px",
                            height: "32px",
                            verticalAlign: "middle",
                            objectFit: "cover",
                            borderRadius: "50%"
                        },
                        attrs: {
                            src: params.row.userimg
                        },
                    });
                }
            }, {
                "title": "昵称",
                "minWidth": 80,
                "ellipsis": true,
                render: (h, params) => {
                    return h('span', params.row.nickname || '-');
                }
            }, {
                "title": "用户名",
                "key": 'username',
                "minWidth": 80,
                "ellipsis": true,
            }, {
                "title": "职位/职称",
                "minWidth": 100,
                "ellipsis": true,
                render: (h, params) => {
                    return h('span', params.row.profession || '-');
                }
            }, {
                "title": "成员角色",
                "minWidth": 100,
                render: (h, params) => {
                    return h('span', params.row.isowner ? '项目负责人' : '成员');
                }
            }, {
                "title": "加入时间",
                "width": 160,
                render: (h, params) => {
                    return h('span', $A.formatDate("Y-m-d H:i:s", params.row.indate));
                }
            }, {
                "title": "操作",
                "key": 'action',
                "width": 80,
                "align": 'center',
                render: (h, params) => {
                    return h('Button', {
                        props: {
                            type: 'primary',
                            size: 'small'
                        },
                        on: {
                            click: () => {
                                this.$Modal.confirm({
                                    title: '移出成员',
                                    content: '你确定要将此成员移出项目吗？',
                                    loading: true,
                                    onOk: () => {
                                        $A.aAjax({
                                            url: 'project/users/join',
                                            data: {
                                                act: 'delete',
                                                projectid: params.row.projectid,
                                                username: params.row.username,
                                            },
                                            error: () => {
                                                this.$Modal.remove();
                                                alert(this.$L('网络繁忙，请稍后再试！'));
                                            },
                                            success: (res) => {
                                                this.$Modal.remove();
                                                this.getLists();
                                                setTimeout(() => {
                                                    if (res.ret === 1) {
                                                        this.$Message.success(res.msg);
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
                    }, '删除');
                }
            }];
        },
        mounted() {
            if (this.canload) {
                this.loadYet = true;
                this.getLists(true);
            }
        },

        watch: {
            projectid() {
                if (this.loadYet) {
                    this.getLists(true);
                }
            },
            canload(val) {
                if (val && !this.loadYet) {
                    this.loadYet = true;
                    this.getLists(true);
                }
            }
        },

        methods: {
            setPage(page) {
                this.listPage = page;
                this.getLists();
            },

            setPageSize(size) {
                if (Math.max($A.runNum(this.listPageSize), 10) != size) {
                    this.listPageSize = size;
                    this.getLists();
                }
            },

            getLists(resetLoad) {
                if (resetLoad === true) {
                    this.listPage = 1;
                }
                if (this.projectid == 0) {
                    this.lists = [];
                    this.listTotal = 0;
                    this.noDataText = "没有相关的数据";
                    return;
                }
                this.loadIng++;
                $A.aAjax({
                    url: 'project/users/lists',
                    data: {
                        page: Math.max(this.listPage, 1),
                        pagesize: Math.max($A.runNum(this.listPageSize), 10),
                        projectid: this.projectid,
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.lists = res.data.lists;
                            this.listTotal = res.data.total;
                        } else {
                            this.lists = [];
                            this.listTotal = 0;
                            this.noDataText = res.msg;
                        }
                    }
                });
            },

            addUser() {
                this.userValue = "";
                this.$Modal.confirm({
                    render: (h) => {
                        return h('div', [
                            h('div', {
                                style: {
                                    fontSize: '16px',
                                    fontWeight: '500',
                                    marginBottom: '20px',
                                }
                            }, '添加成员'),
                            h('UseridInput', {
                                props: {
                                    value: this.userValue,
                                    multiple: true,
                                    placeholder: '请输入昵称/用户名搜索'
                                },
                                on: {
                                    input: (val) => {
                                        this.userValue = val;
                                    }
                                }
                            })
                        ])
                    },
                    loading: true,
                    onOk: () => {
                        if (this.userValue) {
                            let username = this.userValue;
                            $A.aAjax({
                                url: 'project/users/join',
                                data: {
                                    act: 'join',
                                    projectid: this.projectid,
                                    username: username,
                                },
                                error: () => {
                                    this.$Modal.remove();
                                    alert(this.$L('网络繁忙，请稍后再试！'));
                                },
                                success: (res) => {
                                    this.$Modal.remove();
                                    this.getLists();
                                    setTimeout(() => {
                                        if (res.ret === 1) {
                                            this.$Message.success(res.msg);
                                        } else {
                                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                        }
                                    }, 350);
                                }
                            });
                        } else {
                            this.$Modal.remove();
                        }
                    },
                });
            }
        }
    }
</script>
