<template>
    <drawer-tabs-container>
        <div class="project-statistics">
            <!-- 标签 -->
            <ul class="state-overview">
                <li :class="[taskType==='未完成'?'active':'']" @click="taskType='未完成'">
                    <div class="yellow">
                        <h1 class="count">{{statistics_unfinished}}</h1>
                        <p>{{$L('未完成任务')}}</p>
                    </div>
                </li>
                <li :class="[taskType==='已超期'?'active':'']" @click="taskType='已超期'">
                    <div class="red">
                        <h1 class="count">{{statistics_overdue}}</h1>
                        <p>{{$L('超期任务')}}</p>
                    </div>
                </li>
                <li :class="[taskType==='已完成'?'active':'']" @click="taskType='已完成'">
                    <div class="terques">
                        <h1 class="count">{{statistics_complete}}</h1>
                        <p>{{$L('已完成任务')}}</p>
                    </div>
                </li>
            </ul>
            <!-- 列表 -->
            <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
            <!-- 分页 -->
            <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>
        </div>
    </drawer-tabs-container>
</template>

<style lang="scss" scoped>
    .project-statistics {
        .tableFill {
            margin: 12px 12px 20px;
        }

        ul.state-overview {
            display: flex;
            align-items: center;

            > li {
                flex: 1;
                cursor: pointer;
                margin: 0 10px 5px;

                > div {
                    position: relative;
                    box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
                    transition: all 0.2s;
                    border-radius: 6px;
                    color: #ffffff;
                    height: 110px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;

                    &.terques {
                        background: #17BE6B;
                    }

                    &.purple {
                        background: #A218A5;
                    }

                    &.red {
                        background: #ED3F14;
                    }

                    &.yellow {
                        background: #FF9900;
                    }

                    &.blue {
                        background: #2D8CF0;
                    }

                    &:hover {
                        box-shadow: 2px 2px 8px 0 rgba(0, 0, 0, 0.38);
                    }

                    &:after {
                        position: absolute;
                        content: "";
                        left: 50%;
                        bottom: 3px;
                        width: 0;
                        height: 2px;
                        transform: translate(-50%, 0);
                        background-color: #FFFFFF;
                        border-radius: 2px;
                        transition: all 0.3s;
                        opacity: 0;
                    }

                    > h1 {
                        font-size: 36px;
                        margin: -2px 0 0;
                        padding: 0;
                        font-weight: 500;
                    }

                    > p {
                        font-size: 18px;
                        margin: 0;
                        padding: 0;
                    }
                }

                &.active {
                    > div {
                        &:after {
                            width: 90%;
                            opacity: 1;
                        }
                    }
                }
            }
        }
    }
</style>
<script>
    import DrawerTabsContainer from "../DrawerTabsContainer";
    import Task from "../../mixins/task";

    /**
     * 项目统计
     */
    export default {
        name: 'ProjectStatistics',
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
        mixins: [
            Task
        ],
        data () {
            return {
                loadYet: false,

                loadIng: 0,

                columns: [],

                taskType: '未完成',

                lists: [],
                listPage: 1,
                listTotal: 0,
                noDataText: "",

                statistics_unfinished: 0,
                statistics_overdue: 0,
                statistics_complete: 0,
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
                "title": this.$L("创建人"),
                "key": 'createuser',
                "minWidth": 80,
                render: (h, params) => {
                    return h('UserView', {
                        props: {
                            username: params.row.createuser
                        }
                    });
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
            $A.setOnTaskInfoListener('components/project/statistics', (act, detail) => {
                if (detail.projectid != this.projectid) {
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
                    case "archived":    // 归档
                        this.lists.some((task, i) => {
                            if (task.id == detail.id) {
                                this.lists.splice(i, 1);
                                if (task.complete) {
                                    this.statistics_complete--;
                                } else {
                                    this.statistics_unfinished++;
                                }
                                return true;
                            }
                        });
                        break;

                    case "unarchived":  // 取消归档
                        let has = false;
                        this.lists.some((task) => {
                            if (task.id == detail.id) {
                                if (task.complete) {
                                    this.statistics_complete++;
                                } else {
                                    this.statistics_unfinished--;
                                }
                                return has = true;
                            }
                        });
                        if (!has) {
                            this.lists.unshift(detail);
                        }
                        break;

                    case "complete":    // 标记完成
                        this.statistics_complete++;
                        this.statistics_unfinished--;
                        break;

                    case "unfinished":  // 标记未完成
                        this.statistics_complete--;
                        this.statistics_unfinished++;
                        break;
                }
            });
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
            },
            taskType() {
                if (this.loadYet) {
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
                    this.noDataText = this.$L("没有相关的数据");
                    return;
                }
                this.loadIng++;
                let tempType = this.taskType;
                this.noDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        page: Math.max(this.listPage, 1),
                        pagesize: Math.max($A.runNum(this.listPageSize), 10),
                        projectid: this.projectid,
                        type: this.taskType,
                        statistics: 1
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.noDataText = this.$L("数据加载失败！");
                    },
                    success: (res) => {
                        if (tempType != this.taskType) {
                            return;
                        }
                        if (res.ret === 1) {
                            this.lists = res.data.lists;
                            this.listTotal = res.data.total;
                            this.noDataText = this.$L("没有相关的数据");
                        } else {
                            this.lists = [];
                            this.listTotal = 0;
                            this.noDataText = res.msg;
                        }
                        this.statistics_unfinished = res.data.statistics_unfinished || 0;
                        this.statistics_overdue = res.data.statistics_overdue || 0;
                        this.statistics_complete = res.data.statistics_complete || 0;
                    }
                });
            },
        }
    }
</script>
