<template>
    <drawer-tabs-container>
        <div class="project-complete">
            <!-- 列表 -->
            <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
            <!-- 分页 -->
            <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>
        </div>
    </drawer-tabs-container>
</template>

<style lang="scss" scoped>
    .project-complete {
        .tableFill {
            margin: 12px 12px 20px;
        }
    }
</style>
<script>
    import DrawerTabsContainer from "../../DrawerTabsContainer";
    import Task from "../../../mixins/task";

    /**
     * 我创建的任务
     */
    export default {
        name: 'HeaderCreate',
        components: {DrawerTabsContainer},
        props: {
            canload: {
                type: Boolean,
                default: true
            },
        },
        mixins: [
            Task
        ],
        data () {
            return {
                loadYet: false,

                loadIng: 0,

                columns: [],

                lists: [],
                listPage: 1,
                listTotal: 0,
                noDataText: "",
            }
        },
        created() {
            this.noDataText = this.$L("数据加载中.....");
            this.columns = [{
                "title": this.$L("任务名称"),
                "key": 'title',
                "minWidth": 120,
                render: (h, params) => {
                    return this.renderTaskTitle(h, params);
                }
            }, {
                "title": this.$L("负责人"),
                "key": 'username',
                "minWidth": 80,
                render: (h, params) => {
                    return h('UserView', {
                        props: {
                            username: params.row.username
                        }
                    });
                }
            }, {
                "title": this.$L("完成"),
                "minWidth": 70,
                "align": "center",
                render: (h, params) => {
                    return h('span', params.row.complete ? '√' : '-');
                }
            }, {
                "title": this.$L("归档"),
                "minWidth": 70,
                "align": "center",
                render: (h, params) => {
                    return h('span', params.row.archived ? '√' : '-');
                }
            }, {
                "title": this.$L("创建时间"),
                "width": 160,
                render: (h, params) => {
                    return h('span', $A.formatDate("Y-m-d H:i:s", params.row.indate));
                }
            }];
        },
        mounted() {
            if (this.canload) {
                this.loadYet = true;
                this.getLists(true);
            }
            $A.setOnTaskInfoListener('components/project/header/create',(act, detail) => {
                if (detail.createuser != $A.getUserName()) {
                    return;
                }
                //
                this.lists.some((task, i) => {
                    if (task.id == detail.id) {
                        this.lists.splice(i, 1, detail);
                        return true;
                    }
                });
                //
                switch (act) {
                    case "delete":      // 删除任务
                        this.lists.some((task, i) => {
                            if (task.id == detail.id) {
                                this.lists.splice(i, 1);
                                return true;
                            }
                        });
                        break;
                }
            });
        },

        watch: {
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
                this.loadIng++;
                this.noDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        createuser: 1,
                        archived: '全部',
                        page: Math.max(this.listPage, 1),
                        pagesize: Math.max($A.runNum(this.listPageSize), 10),
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.noDataText = this.$L("数据加载失败！");
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.lists = res.data.lists;
                            this.listTotal = res.data.total;
                            this.noDataText = this.$L("没有相关的数据");
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
