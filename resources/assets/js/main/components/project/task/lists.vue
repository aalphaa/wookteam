<template>
    <div class="project-task-lists">

        <!-- 搜索 -->
        <Row class="sreachBox">
            <div class="item">
                <div class="item-4">
                    <sreachTitle :val="keys.type">状态</sreachTitle>
                    <Select v-model="keys.type" placeholder="全部">
                        <Option value="">全部</Option>
                        <Option value="未完成">未完成</Option>
                        <Option value="已超期">已超期</Option>
                        <Option value="已完成">已完成</Option>
                    </Select>
                </div>
                <div class="item-4">
                    <sreachTitle :val="keys.username">负责人</sreachTitle>
                    <Input v-model="keys.username" placeholder="用户名"/>
                </div>
                <div class="item-4">
                    <sreachTitle :val="keys.level">级别</sreachTitle>
                    <Select v-model="keys.level" placeholder="全部">
                        <Option value="">全部</Option>
                        <Option value="1">P1</Option>
                        <Option value="2">P2</Option>
                        <Option value="3">P3</Option>
                        <Option value="4">P4</Option>
                    </Select>
                </div>
                <div class="item-4">
                    <sreachTitle :val="keys.labelid">阶段</sreachTitle>
                    <Select v-model="keys.labelid" placeholder="全部">
                        <Option value="">全部</Option>
                        <Option
                            v-for="item in labelLists"
                            :value="item.id"
                            :key="item.id">{{ item.title }}</Option>
                    </Select>
                </div>
            </div>
            <div class="item item-button">
                <Button type="text" v-if="$A.objImplode(keys)!=''" @click="sreachTab(true)">取消筛选</Button>
                <Button type="primary" icon="md-search" :loading="loadIng > 0" @click="sreachTab">搜索</Button>
            </div>
        </Row>

        <!-- 列表 -->
        <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>

        <!-- 分页 -->
        <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>

    </div>
</template>
<style lang="scss" scoped>
    .project-task-lists {
        margin: 0 12px;
        .tableFill {
            margin: 12px 0 20px;
        }
    }
</style>

<script>

    export default {
        name: 'ProjectTaskLists',
        props: {
            projectid: {
                default: 0
            },
            canload: {
                type: Boolean,
                default: true
            },
            labelLists: {
                type: Array,
            },
        },
        data() {
            return {
                keys: {},

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
                "title": "阶段",
                "key": 'labelid',
                "minWidth": 80,
                render: (h, params) => {
                    let labelid = params.row.labelid;
                    let labelDetail = this.labelLists.find((item) => { return item.id === labelid});
                    return h('span', labelDetail ? labelDetail.title : labelid);
                }
            }, {
                "title": "计划时间",
                "width": 160,
                "align": "center",
                render: (h, params) => {
                    return h('span', params.row.enddate ? $A.formatDate("Y-m-d H:i:s", params.row.enddate) : '-');
                }
            }, {
                "title": "负责人",
                "key": 'username',
                "minWidth": 80,
            }, {
                "title": "优先级",
                "key": 'level',
                "align": "center",
                "minWidth": 70,
                "maxWidth": 80,
                render: (h, params) => {
                    let level = params.row.level;
                    let color;
                    switch (level) {
                        case "1":
                            color = "#ff0000";
                            break;
                        case "2":
                            color = "#BB9F35";
                            break;
                        case "3":
                            color = "#449EDD";
                            break;
                        case "4":
                            color = "#84A83B";
                            break;
                    }
                    return h('span', {
                        style: {
                            color: color
                        }
                    }, "P" + level);
                },
            }, {
                "title": "状态",
                "key": 'complete',
                "align": "center",
                "minWidth": 70,
                "maxWidth": 80,
                render: (h, params) => {
                    let color;
                    let status;
                    if (params.row.overdue) {
                        color = "#ff0000";
                        status = "已超期";
                    } else if (params.row.complete) {
                        color = "";
                        status = "已完成";
                    } else {
                        color = "#19be6b";
                        status = "未完成";
                    }
                    return h('span', {
                        style: {
                            color: color
                        }
                    }, status);
                },
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
            }
        },

        methods: {
            sreachTab(clear) {
                if (clear === true) {
                    this.keys = {};
                }
                this.getLists(true);
            },

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
                let whereData = $A.cloneData(this.keys);
                whereData.page = Math.max(this.listPage, 1);
                whereData.pagesize = Math.max($A.runNum(this.listPageSize), 10);
                whereData.projectid = this.projectid;
                $A.aAjax({
                    url: 'project/task/lists',
                    data: whereData,
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
