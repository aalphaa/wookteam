<template>
    <drawer-tabs-container>
        <div class="todo-calendar">
            <FullCalendar :events="lists" :loading="loadIng>0" @eventClick="clickEvent" lang="zh" @change="changeDateRange"></FullCalendar>
        </div>
    </drawer-tabs-container>
</template>

<style lang="scss" scoped>
    .todo-calendar {
    }
</style>
<script>
    import DrawerTabsContainer from "../../DrawerTabsContainer";
    import FullCalendar from "../../FullCalendar/FullCalendar";
    export default {
        name: 'TodoCalendar',
        components: {FullCalendar, DrawerTabsContainer},
        props: {

        },
        data () {
            return {
                loadIng: 0,

                lists: [],

                startdate: '',
                enddate: '',
            }
        },
        created() {

        },
        mounted() {
            $A.setOnTaskInfoListener('components/project/todo/calendar',(act, detail) => {
                detail = this.formatTaskData(detail);
                this.lists.some((task, i) => {
                    if (task.id == detail.id) {
                        this.lists.splice(i, 1, detail);
                        return true;
                    }
                });
                //
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
            });
        },
        methods: {
            clickEvent(event){
                this.taskDetail(event.id);
            },

            changeDateRange(startdate, enddate){
                this.startdate = startdate;
                this.enddate = enddate;
                this.getLists(1);
            },

            getLists(page) {
                this.lists = [];
                this.loadIng++;
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        startdate: this.startdate,
                        enddate: this.enddate,
                        page: page,
                        pagesize: 100
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            let inLists,
                                data;
                            res.data.lists.forEach((temp) => {
                                data = this.formatTaskData(temp);
                                inLists = false;
                                this.lists.some((item, i) => {
                                    if (item.id == data.id) {
                                        this.lists.splice(i, 1, data);
                                        return inLists = true;
                                    }
                                });
                                if (!inLists) {
                                    this.lists.push(data);
                                }
                            });
                            if (res.data.hasMorePages === true) {
                                this.getLists(res.data.currentPage + 1)
                            }
                        }
                    }
                });
            },

            formatTaskData(taskData) {
                let title = taskData.title;
                let startdate = taskData.startdate || taskData.indate;
                let enddate = taskData.enddate || taskData.indate;
                if (startdate != enddate) {
                    let ymd1 = $A.formatDate('Y-m-d', startdate)
                    let ymd2 = $A.formatDate('Y-m-d', enddate)
                    let time = ymd1 + "~" + ymd2;
                    if (ymd1 == ymd2) {
                        time = $A.formatDate('H:i', startdate) + "~" + $A.formatDate('H:i', enddate);
                    } else if (ymd1.substring(0, 4) == ymd2.substring(0, 4)) {
                        time = $A.formatDate('m-d', startdate) + "~" + $A.formatDate('m-d', enddate);
                    }
                    title = time + " " + title;
                } else {
                    title = $A.formatDate('H:i', startdate) + " " + title;
                }
                //
                if (taskData.complete) {
                    title = '<span style="text-decoration:line-through">' + title + '</span>';
                }
                let color = '#D8F8F2';
                if (taskData.level === 1) {
                    color = 'rgba(248, 14, 21, 0.6)';
                }else if (taskData.level === 2) {
                    color = 'rgba(236, 196, 2, 0.5)';
                }else if (taskData.level === 3) {
                    color = 'rgba(0, 159, 227, 0.7)';
                }else if (taskData.level === 4) {
                    color = 'rgba(121, 170, 28, 0.7)';
                }
                return {
                    "id": taskData.id,
                    "start": $A.formatDate('Y-m-d H:i:s', startdate),
                    "end": $A.formatDate('Y-m-d H:i:s', enddate),
                    "title": title,
                    "color": color,
                    "avatar": taskData.userimg,
                    "name": taskData.nickname || taskData.username
                };
            }
        }
    }
</script>
