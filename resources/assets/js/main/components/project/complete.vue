<template>
    <div class="project-complete">
        <!-- 列表 -->
        <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
        <!-- 分页 -->
        <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>
    </div>
</template>

<style lang="scss" scoped>
    .project-complete {
        .tableFill {
            margin: 12px 12px 20px;
        }
    }
</style>
<script>
    export default {
        name: 'ProjectComplete',
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
                "title": "任务名称",
                "key": 'title',
                "minWidth": 100,
            }, {
                "title": "创建人",
                "key": 'createuser',
                "minWidth": 80,
            }, {
                "title": "负责人",
                "key": 'username',
                "minWidth": 80,
            }, {
                "title": "归档时间",
                "width": 160,
                render: (h, params) => {
                    return h('span', $A.formatDate("Y-m-d H:i:s", params.row.archiveddate));
                }
            }, {
                "title": "操作",
                "key": 'action',
                "width": 100,
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
                                    title: '取消归档',
                                    content: '你确定要取消归档吗？',
                                    loading: true,
                                    onOk: () => {
                                        $A.aAjax({
                                            url: 'project/task/archived',
                                            data: {
                                                act: 'cancel',
                                                taskid: params.row.id,
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
                                                        this.getLists();
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
                    }, '取消归档');
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
                    url: 'project/task/lists',
                    data: {
                        page: Math.max(this.listPage, 1),
                        pagesize: Math.max($A.runNum(this.listPageSize), 10),
                        projectid: this.projectid,
                        archived: 1,
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
        }
    }
</script>
