<template>
    <div class="project-statistics">
        <!-- 标签 -->
        <ul class="state-overview">
            <li :class="[taskType==='未完成'?'active':'']" @click="taskType='未完成'">
                <div class="yellow">
                    <h1 class="count">{{statistics_unfinished}}</h1>
                    <p>未完成任务</p>
                </div>
            </li>
            <li :class="[taskType==='已超期'?'active':'']" @click="taskType='已超期'">
                <div class="red">
                    <h1 class="count">{{statistics_overdue}}</h1>
                    <p>超期任务</p>
                </div>
            </li>
            <li :class="[taskType==='已完成'?'active':'']" @click="taskType='已完成'">
                <div class="terques">
                    <h1 class="count">{{statistics_complete}}</h1>
                    <p>已完成任务</p>
                </div>
            </li>
        </ul>
        <!-- 列表 -->
        <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
        <!-- 分页 -->
        <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>
    </div>
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
                    -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
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
    export default {
        name: 'ProjectStatistics',
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

                taskType: '未完成',

                lists: [],
                listPage: 1,
                listTotal: 0,
                noDataText: "数据加载中.....",

                statistics_unfinished: 0,
                statistics_overdue: 0,
                statistics_complete: 0,
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
                "title": "创建时间",
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
                        type: this.taskType,
                        statistics: 1
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.lists = res.data.lists;
                            this.listTotal = res.data.total;
                            this.statistics_unfinished = res.data.statistics_unfinished;
                            this.statistics_overdue = res.data.statistics_overdue;
                            this.statistics_complete = res.data.statistics_complete;
                        } else {
                            this.lists = [];
                            this.listTotal = 0;
                            this.noDataText = res.msg;
                            this.statistics_unfinished = 0;
                            this.statistics_overdue = 0;
                            this.statistics_complete = 0;
                        }
                    }
                });
            },
        }
    }
</script>
