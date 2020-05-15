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

        },

        methods: {
            clickEvent(event, jsEvent){
                console.log(event, jsEvent)
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
                                let title = temp.title;
                                let startdate = temp.startdate || temp.indate;
                                let enddate = temp.enddate || temp.indate;
                                if (startdate != enddate) {
                                    title = $A.formatDate('H:i', startdate) + "~" + $A.formatDate('H:i', enddate) + " " + title;
                                } else {
                                    title = $A.formatDate('H:i', startdate) + " " + title;
                                }
                                data = {
                                    "id": temp.id,
                                    "start": $A.formatDate('Y-m-d H:i:s', startdate),
                                    "end": $A.formatDate('Y-m-d H:i:s', enddate),
                                    "title": title,
                                    "color": "#D8F8F2",
                                    "avatar": temp.userimg,
                                    "name": temp.nickname || temp.username
                                };
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
        }
    }
</script>
