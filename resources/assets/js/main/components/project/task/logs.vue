<template>
    <drawer-tabs-container>
        <div class="project-task-logs">
            <ul class="logs-activity">
                <li v-for="(item, date) in lists">
                    <div class="logs-date">{{date}}</div>
                    <div class="logs-section">
                        <Timeline>
                            <TimelineItem color="green">
                                <span>发布里程碑版本</span>
                            </TimelineItem>
                            <TimelineItem>发布1.0版本</TimelineItem>
                            <TimelineItem>发布2.0版本</TimelineItem>
                            <TimelineItem>发布3.0版本</TimelineItem>
                        </Timeline>
                    </div>
                </li>
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
            > li {
                padding-top: 22px;
                &:first-child {
                    padding-top: 0;
                }
                .logs-date {
                    color: rgba(0, 0, 0, .36);
                    padding-bottom: 14px;
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
                hasMorePages: false,
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
                    this.getLists(true);
                }
            },
            taskid() {
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
            getLists(resetLoad) {
                if (resetLoad === true) {
                    this.listPage = 1;
                }
                if (this.projectid == 0) {
                    this.lists = {};
                    this.hasMorePages = false;
                    return;
                }
                this.loadIng++;
                $A.aAjax({
                    url: 'project/log/lists',
                    data: {
                        projectid: this.projectid,
                        taskid: this.taskid,
                        pagesize: 100,
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            let timeData,
                                ymd;
                            res.data.lists.forEach((item) => {
                                timeData = item.timeData;
                                ymd = timeData.ymd;
                                if (typeof this.lists[ymd] !== "object") {
                                    this.$set(this.lists, ymd, []);
                                }
                                this.lists[ymd].push(item);
                            });
                            console.log(this.lists);
                            this.hasMorePages = res.data.hasMorePages;
                        } else {
                            this.lists = [];
                            this.hasMorePages = false;
                        }
                    }
                });
            },
        }
    }
</script>
