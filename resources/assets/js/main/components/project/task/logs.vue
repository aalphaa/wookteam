<template>
    <drawer-tabs-container>
        <div class="project-task-logs">
            <ul class="logs-activity" :class="`${taskid>0?'istaskid':''}`">
                <li v-for="items in lists">
                    <div class="logs-date">{{logDate(items)}}</div>
                    <div class="logs-section">
                        <Timeline>
                            <TimelineItem v-for="(item, index) in items.lists" :key="index">
                                <div slot="dot" class="logs-dot">
                                    <img :src="item.userimg"/>
                                </div>
                                <div class="log-summary">
                                    <span class="log-creator"><UserView :username="item.username"/></span>
                                    <span class="log-text-secondary">{{item.detail}}</span>
                                    <span v-if="item.other.type=='task' && taskid == 0" class="log-text-link" @click="taskDetail(item.other.id)">{{item.other.title}}</span>
                                    <a v-if="item.other.type=='file'" class="log-text-link" target="_blank" :href="fileDownUrl(item.other.id)">{{item.other.name}}</a>
                                    <span class="log-text-info">{{item.timeData.ymd}} {{item.timeData.segment}} {{item.timeData.hi}}</span></div>
                            </TimelineItem>
                        </Timeline>
                    </div>
                </li>
                <li v-if="loadIng > 0" class="logs-loading"><w-loading></w-loading></li>
                <li v-else-if="hasMorePages" class="logs-more" @click="getMore">{{$L('加载更多')}}</li>
                <li v-else-if="totalNum == 0" class="logs-none" @click="getLists(true)">{{$L('没有相关内容')}}</li>
            </ul>
        </div>
    </drawer-tabs-container>
</template>
<style lang="scss" scoped>
    .project-task-logs {
        margin: 0 12px;
        .logs-activity {
            position: relative;
            word-break: break-all;
            padding: 12px 12px;
            &.istaskid {
                > li {
                    padding-top: 0;
                }
            }
            > li {
                padding-top: 22px;
                &.logs-loading {
                    margin: 4px 0;
                    width: 18px;
                    height: 18px;
                    display: flex;
                }
                &.logs-more {
                    cursor: pointer;
                    &:hover {
                        color: #048be0;
                    }
                }
                &.logs-none {
                    cursor: pointer;
                    color: #666666;
                    line-height: 26px;
                }
                &:first-child {
                    padding-top: 0;
                }
                &:last-child {
                    .logs-section {
                        margin-bottom: -8px;
                    }
                }
                .logs-date {
                    color: rgba(0, 0, 0, .36);
                    padding-bottom: 14px;
                }
                .logs-section {
                    .ivu-timeline-item {
                        padding-bottom: 2px;
                    }
                }
                .logs-dot {
                    width: 18px;
                    height: 18px;
                    margin-left: 10px;
                    img {
                        width: 100%;
                        height: 100%;
                        border-radius: 50%;
                        overflow: hidden;
                    }
                }
                .log-summary {
                    > span,
                    > a {
                        padding-right: 6px;
                        word-wrap: break-word;
                        word-break: break-word;
                    }
                    .log-creator {
                        color:rgba(0, 0, 0, 0.85)
                    }
                    .log-text-secondary {
                        color: rgba(0,0,0,.54);
                    }
                    .log-text-link {
                        color: #048be0;
                        cursor: pointer;
                    }
                    .log-text-info {
                        color: rgba(0,0,0,.36);
                    }
                }
            }
        }
    }
</style>

<script>

    import DrawerTabsContainer from "../../DrawerTabsContainer";

    export default {
        name: 'ProjectTaskLogs',
        components: {DrawerTabsContainer},
        props: {
            projectid: {
                default: 0
            },
            taskid: {
                default: 0
            },
            pagesize: {
                default: 100
            },
            logtype: {
                default: '全部'
            },
            canload: {
                type: Boolean,
                default: true
            },
        },
        data() {
            return {
                loadYet: false,

                loadIng: 0,

                lists: {},
                listPage: 1,
                hasMorePages: false,
                totalNum: -1,
            }
        },

        created() {

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
                    this.lists = {};
                    this.getLists(true);
                }
            },
            taskid() {
                if (this.loadYet) {
                    this.lists = {};
                    this.getLists(true);
                }
            },
            logtype() {
                if (this.loadYet) {
                    this.lists = {};
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
            logDate(items) {
                let md = $A.formatDate("m-d");
                return md == items.ymd ? (items.ymd + ' ' + this.$L('今天')) : items.key;
            },

            getLists(resetLoad, noLoading) {
                if (resetLoad === true) {
                    this.listPage = 1;
                }
                if (noLoading !== true) {
                    this.loadIng++;
                }
                $A.aAjax({
                    url: 'project/log/lists',
                    data: {
                        projectid: this.projectid,
                        taskid: this.taskid,
                        type: this.logtype,
                        page: Math.max(this.listPage, 1),
                        pagesize: this.pagesize,
                    },
                    complete: () => {
                        if (noLoading !== true) {
                            this.loadIng--;
                        }
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            let timeData,
                                key;
                            if (resetLoad === true) {
                                this.lists = {};
                            }
                            res.data.lists.forEach((item) => {
                                timeData = item.timeData;
                                key = timeData.ymd + " " + timeData.week;
                                if (typeof this.lists[key] !== "object") {
                                    this.$set(this.lists, key, {
                                        key: key,
                                        ymd: timeData.ymd,
                                        lists: [],
                                    });
                                }
                                this.lists[key].lists.push(item);
                            });
                            this.hasMorePages = res.data.hasMorePages;
                            this.totalNum = res.data.total;
                        } else {
                            this.lists = {};
                            this.hasMorePages = false;
                            this.totalNum = 0;
                        }
                    }
                });
            },

            getMore() {
                if (!this.hasMorePages) {
                    return;
                }
                this.hasMorePages = false;
                this.listPage++;
                this.getLists();
            },

            fileDownUrl(id) {
                return $A.aUrl('project/files/download?fileid=' + id);
            }
        }
    }
</script>
