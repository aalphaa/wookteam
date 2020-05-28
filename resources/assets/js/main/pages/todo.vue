<template>
    <div class="w-main todo">

        <v-title>{{$L('待办')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="todo"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <div class="page-nav-left">
                        <span><i class="ft icon">&#xE787;</i> {{$L('我的待办')}}</span>
                        <div v-if="loadIng > 0" class="page-nav-loading"><w-loading></w-loading></div>
                        <div v-else class="page-nav-refresh"><em @click="refreshTask">{{$L('刷新')}}</em></div>
                    </div>
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover" @click="handleTodo('calendar')"><i class="ft icon">&#xE706;</i> {{$L('待办日程')}}</span>
                    <span class="ft hover" @click="handleTodo('complete')"><i class="ft icon">&#xE73D;</i> {{$L('已完成的任务')}}</span>
                    <span class="ft hover" @click="handleTodo('attention')"><i class="ft icon">&#xE748;</i> {{$L('我关注的任务')}}</span>
                    <span class="ft hover" @click="handleTodo('report')"><i class="ft icon">&#xE743;</i> {{$L('周报/日报')}}</span>
                </div>
            </div>
        </div>

        <w-content>
            <div class="todo-main">
                <div v-for="subs in [['1', '2'], ['3', '4']]" class="todo-ul">
                    <div v-for="index in subs" class="todo-li">
                        <div class="todo-card">
                            <div class="todo-card-head" :class="['p' + index]">
                                <i class="ft icon flag">&#xE753;</i>
                                <div class="todo-card-title">{{pTitle(index)}}</div>
                                <label class="todo-input-box" :class="{active: !!taskDatas[index].focus}" @click="()=>{$set(taskDatas[index],'focus',true)}">
                                    <div class="todo-input-ibox" @click.stop="">
                                        <Input v-model="taskDatas[index].title" class="todo-input-enter" :placeholder="$L('在这里输入事项，回车即可保存')" @on-enter="addTask(index)"></Input>
                                        <div class="todo-input-close" @click="()=>{$set(taskDatas[index],'focus',false)}"><i class="ft icon">&#xE710;</i></div>
                                    </div>
                                    <div class="todo-input-pbox">
                                        <div class="todo-input-placeholder">{{$L('点击可快速添加需要处理的事项')}}</div>
                                        <div class="todo-input-close"><i class="ft icon">&#xE740;</i></div>
                                    </div>
                                </label>
                            </div>
                            <div class="todo-card-content">
                                <draggable
                                    v-if="taskDatas[index].lists.length > 0"
                                    v-model="taskDatas[index].lists"
                                    class="content-ul"
                                    group="task"
                                    draggable=".task-draggable"
                                    :animation="150"
                                    :disabled="taskSortDisabled"
                                    @sort="taskSortUpdate"
                                    @remove="taskSortUpdate">
                                    <div v-for="task in taskDatas[index].lists" class="content-li task-draggable" :key="task.id" :class="{complete:task.complete}" @click="openTaskModal(task)">
                                        <Icon v-if="task.complete" class="task-check" type="md-checkbox-outline" @click.stop="taskComplete(task, false)"/>
                                        <Icon v-else class="task-check" type="md-square-outline" @click.stop="taskComplete(task, true)"/>
                                        <div v-if="!!task.loadIng" class="task-loading"><w-loading></w-loading></div>
                                        <div v-if="task.overdue" class="task-overdue">[{{$L('超期')}}]</div>
                                        <div class="task-title">{{task.title}}</div>
                                    </div>
                                    <div v-if="taskDatas[index].hasMorePages === true" class="content-li more" @click="getTaskLists(index, true)">{{$L('加载更多')}}</div>
                                </draggable>
                                <div v-else-if="taskDatas[index].loadIng == 0" class="content-empty">{{$L('恭喜你！已完成了所有待办')}}</div>
                                <div v-if="taskDatas[index].loadIng > 0" class="content-loading"><w-loading></w-loading></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </w-content>

        <Drawer v-model="todoDrawerShow" width="75%">
            <Tabs v-if="todoDrawerShow" v-model="todoDrawerTab">
                <TabPane :label="$L('待办日程')" name="calendar">
                    <todo-calendar :canload="todoDrawerShow && todoDrawerTab == 'calendar'"></todo-calendar>
                </TabPane>
                <TabPane :label="$L('已完成的任务')" name="complete">
                    <todo-complete :canload="todoDrawerShow && todoDrawerTab == 'complete'"></todo-complete>
                </TabPane>
                <TabPane :label="$L('我关注的任务')" name="attention">
                    <todo-attention :canload="todoDrawerShow && todoDrawerTab == 'attention'"></todo-attention>
                </TabPane>
            </Tabs>
        </Drawer>

        <Drawer v-model="todoReportDrawerShow" width="75%">
            <Tabs v-if="todoReportDrawerShow" v-model="todoReportDrawerTab">
                <TabPane :label="$L('我的汇报')" name="my">
                    <report-my :canload="todoReportDrawerShow && todoReportDrawerTab == 'my'"></report-my>
                </TabPane>
                <TabPane :label="$L('收到的汇报')" name="receive">
                    <report-receive :canload="todoReportDrawerShow && todoReportDrawerTab == 'receive'"></report-receive>
                </TabPane>
            </Tabs>
        </Drawer>
    </div>
</template>

<style lang="scss">
    .todo-input-enter {
        .ivu-input {
            border: 0;
            background-color: rgba(255, 255, 255, 0.9);
        }
    }
</style>
<style lang="scss" scoped>
    .todo {
        .todo-main {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            min-height: 500px;
            padding: 5px;
            .todo-ul {
                flex: 1;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                width: 100%;
                .todo-li {
                    flex: 1;
                    height: 100%;
                    position: relative;
                    .todo-card {
                        position: absolute;
                        top: 10px;
                        left: 10px;
                        right: 10px;
                        bottom: 10px;
                        display: flex;
                        flex-direction: column;
                        .todo-card-head {
                            display: flex;
                            align-items: center;
                            padding: 0 10px;
                            height: 38px;
                            border-radius: 4px 4px 0 0;
                            color: #ffffff;
                            .ft.icon {
                                transform: scale(1);
                            }
                            .flag {
                                font-weight: bold;
                                font-size: 14px;
                                margin-right: 5px;
                                min-width: 16px;
                            }
                            .todo-card-title {
                                font-weight: bold;
                            }
                            .todo-input-box {
                                flex: 1;
                                display: flex;
                                flex-direction: row;
                                align-items: center;
                                justify-content: flex-end;
                                height: 100%;
                                cursor: pointer;
                                &:hover {
                                    .todo-input-placeholder {
                                        opacity: 1;
                                    }
                                }
                                &.active {
                                    .todo-input-pbox {
                                        display: none;
                                    }
                                    .todo-input-ibox {
                                        display: flex;
                                    }
                                }
                                .todo-input-placeholder {
                                    color: rgba(255, 255, 255, 0.6);
                                    padding-right: 6px;
                                    transition: all 0.2s;
                                    opacity: 0;
                                }
                                .todo-input-pbox,
                                .todo-input-ibox {
                                    flex: 1;
                                    display: flex;
                                    flex-direction: row;
                                    align-items: center;
                                    justify-content: flex-end;
                                    padding-left: 14px;
                                    height: 100%;
                                }
                                .todo-input-ibox {
                                    display: none;
                                }
                                .todo-input-close {
                                    height: 100%;
                                    display: flex;
                                    align-items: center;
                                    padding-left: 8px;
                                    i {
                                        font-size: 18px;
                                        font-weight: normal;
                                    }
                                }
                            }
                            &.p1 {
                                background: rgba(248, 14, 21, 0.6);
                            }
                            &.p2 {
                                background: rgba(236, 196, 2, 0.5);
                            }
                            &.p3 {
                                background: rgba(0, 159, 227, 0.7);
                            }
                            &.p4 {
                                background: rgba(121, 170, 28, 0.7);
                            }
                        }
                        .todo-card-content {
                            flex: 1;
                            background-color: #f5f6f7;
                            border-radius: 0 0 4px 4px;
                            overflow: auto;
                            transform: translateZ(0);
                            .content-ul {
                                display: flex;
                                flex-direction: column;
                                .content-li {
                                    display: flex;
                                    flex-direction: row;
                                    align-items: flex-start;
                                    width: 100%;
                                    padding: 8px;
                                    color: #444444;
                                    border-bottom: dotted 1px rgba(153, 153, 153, 0.25);
                                    position: relative;
                                    cursor: pointer;
                                    &.complete {
                                        color: #999999;
                                        .task-title {
                                            text-decoration: line-through;
                                        }
                                    }
                                    &.more {
                                        color: #666;
                                        justify-content: center;
                                    }
                                    .task-check {
                                        font-size: 16px;
                                        padding-right: 6px;
                                        padding-top: 3px;
                                    }
                                    .task-loading {
                                        width: 15px;
                                        height: 15px;
                                        margin-right: 6px;
                                        margin-top: 3px;
                                    }
                                    .task-overdue {
                                        color: #ff0000;
                                        padding-right: 2px;
                                    }
                                    .task-title {
                                        flex: 1;
                                        word-break: break-all;
                                        &:hover {
                                            color: #000000;
                                        }
                                    }
                                }
                            }
                            .content-loading {
                                width: 100%;
                                height: 22px;
                                text-align: center;
                                margin-top: 8px;
                                margin-bottom: 8px;
                            }
                            .content-empty {
                                margin: 20px auto;
                                text-align: center;
                                color: #666;
                            }
                        }
                    }
                }
            }
        }
        @media (max-width: 780px) {
            .todo-main {
                height: auto;
                .todo-ul {
                    flex-direction: column;
                    .todo-li {
                        width: 100%;
                        .todo-card {
                            position: static;
                            top: 0;
                            right: 0;
                            left: 0;
                            bottom: 0;
                            margin: 10px;
                            display: flex;
                            flex-direction: column;
                            min-height: 320px;
                            max-height: 520px;
                        }
                    }
                }
            }
        }
    }
</style>
<script>
    import draggable from 'vuedraggable'

    import WHeader from "../components/WHeader";
    import WContent from "../components/WContent";
    import WLoading from "../components/WLoading";
    import TodoCalendar from "../components/project/todo/calendar";
    import TodoComplete from "../components/project/todo/complete";
    import TodoAttention from "../components/project/todo/attention";

    import Task from "../mixins/task";
    import ReportMy from "../components/report/my";
    import ReportReceive from "../components/report/receive";

    export default {
        components: {
            ReportReceive,
            ReportMy, draggable, TodoAttention, TodoComplete, TodoCalendar, WContent, WHeader, WLoading},
        mixins: [
            Task
        ],
        data () {
            return {
                loadIng: 0,

                userInfo: {},

                taskDatas: {
                    "1": {lists: [], hasMorePages: false},
                    "2": {lists: [], hasMorePages: false},
                    "3": {lists: [], hasMorePages: false},
                    "4": {lists: [], hasMorePages: false},
                },

                taskSortData: '',
                taskSortDisabled: false,

                todoDrawerShow: false,
                todoDrawerTab: 'calendar',

                todoReportDrawerShow: false,
                todoReportDrawerTab: 'my',
            }
        },
        mounted() {
            this.refreshTask();
            this.userInfo = $A.getUserInfo((res, isLogin) => {
                if (this.userInfo.id != res.id) {
                    this.userInfo = res;
                    isLogin && this.refreshTask();
                }
            }, false);
            //
            $A.setOnTaskInfoListener('pages/todo',(act, detail) => {
                if (detail.username != $A.getUserName()) {
                    for (let level in this.taskDatas) {
                        this.taskDatas[level].lists.some((task, i) => {
                            if (task.id == detail.id) {
                                this.taskDatas[level].lists.splice(i, 1);
                                this.taskSortData = this.getTaskSort();
                                return true;
                            }
                        });
                    }
                    return;
                }
                //
                switch (act) {
                    case 'deleteproject':   // 删除项目
                    case 'deletelabel':     // 删除分类
                        this.refreshTask();
                        return;
                    case 'tasklevel':       // 调整级别
                        return;
                }
                //
                for (let level in this.taskDatas) {
                    this.taskDatas[level].lists.some((task, i) => {
                        if (task.id == detail.id) {
                            this.taskDatas[level].lists.splice(i, 1, detail);
                            return true;
                        }
                    });
                }
                //
                switch (act) {
                    case "title":           // 标题
                    case "desc":            // 描述
                    case "plannedtime":     // 设置计划时间
                    case "unplannedtime":   // 取消计划时间
                    case "complete":        // 标记完成
                    case "unfinished":      // 标记未完成
                    case "comment":         // 评论
                        // 这些都不用处理
                        break;

                    case "level":           // 优先级
                        for (let level in this.taskDatas) {
                            this.taskDatas[level].lists.some((task, i) => {
                                if (task.id == detail.id) {
                                    this.taskDatas[level].lists.splice(i, 1);
                                    return true;
                                }
                            });
                            if (level == detail.level) {
                                let index = this.taskDatas[level].lists.length;
                                this.taskDatas[level].lists.some((task, i) => {
                                    if (detail.userorder > task.userorder || (detail.userorder == task.userorder && detail.id > task.id)) {
                                        index = i;
                                        return true;
                                    }
                                });
                                this.taskDatas[level].lists.splice(index, 0, detail);
                            }
                        }
                        this.taskSortData = this.getTaskSort();
                        break;

                    case "username":        // 负责人
                    case "delete":          // 删除任务
                    case "archived":        // 归档
                        for (let level in this.taskDatas) {
                            this.taskDatas[level].lists.some((task, i) => {
                                if (task.id == detail.id) {
                                    this.taskDatas[level].lists.splice(i, 1);
                                    return true;
                                }
                            });
                        }
                        this.taskSortData = this.getTaskSort();
                        break;

                    case "unarchived":      // 取消归档
                        for (let level in this.taskDatas) {
                            if (level == detail.level) {
                                let index = this.taskDatas[level].lists.length;
                                this.taskDatas[level].lists.some((task, i) => {
                                    if (detail.userorder > task.userorder || (detail.userorder == task.userorder && detail.id > task.id)) {
                                        index = i;
                                        return true;
                                    }
                                });
                                this.taskDatas[level].lists.splice(index, 0, detail);
                            }
                        }
                        this.taskSortData = this.getTaskSort();
                        break;
                }
            }, true);
        },
        deactivated() {
            this.todoDrawerShow = false;
            this.todoReportDrawerShow = false;
        },
        computed: {

        },
        watch: {

        },
        methods: {
            pTitle(p) {
                switch (p) {
                    case "1":
                        return this.$L("重要且紧急");
                    case "2":
                        return this.$L("重要不紧急");
                    case "3":
                        return this.$L("紧急不重要");
                    case "4":
                        return this.$L("不重要不紧急");
                }
            },

            refreshTask() {
                this.taskDatas = {
                    "1": {lists: [], hasMorePages: false},
                    "2": {lists: [], hasMorePages: false},
                    "3": {lists: [], hasMorePages: false},
                    "4": {lists: [], hasMorePages: false},
                };
                for (let i = 1; i <= 4; i++) {
                    this.getTaskLists(i.toString());
                }
            },

            getTaskLists(index, isNext) {
                let taskData = this.taskDatas[index];
                let currentPage = 1;
                let pagesize = 20;
                let withNextPage = false;
                //
                if (isNext === true) {
                    if (taskData.hasMorePages !== true) {
                        return;
                    }
                    currentPage = Math.max(1, $A.runNum(taskData['currentPage']));
                    let tempLists = this.taskDatas[detail.level].lists.filter((item) => { return item.complete == 0; });
                    if (tempLists.length >= currentPage * pagesize) {
                        currentPage++;
                    } else {
                        withNextPage = true;
                    }
                }
                this.$set(taskData, 'hasMorePages', false);
                this.$set(taskData, 'loadIng', $A.runNum(taskData.loadIng) + 1);
                this.taskSortDisabled = true;
                this.loadIng++;
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        level: index,
                        sorts: {key:'userorder', order:'desc'},
                        page: currentPage,
                        pagesize: pagesize,
                    },
                    complete: () => {
                        this.loadIng--;
                        this.taskSortDisabled = false;
                        this.$set(taskData, 'loadIng', $A.runNum(taskData.loadIng) - 1);
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            let inLists;
                            res.data.lists.forEach((data) => {
                                inLists = false;
                                taskData.lists.some((item, i) => {
                                    if (item.id == data.id) {
                                        taskData.lists.splice(i, 1, data);
                                        return inLists = true;
                                    }
                                });
                                if (!inLists) {
                                    taskData.lists.push(data);
                                }
                            });
                            this.taskSortData = this.getTaskSort();
                            this.$set(taskData, 'currentPage', res.data.currentPage);
                            this.$set(taskData, 'hasMorePages', res.data.hasMorePages);
                            if (res.data.currentPage && withNextPage) {
                                this.getTaskLists(index, true);
                            }
                        } else {
                            this.$set(taskData, 'lists', []);
                            this.$set(taskData, 'hasMorePages', false);
                        }
                    }
                });
            },

            addTask(index) {
                let taskData = this.taskDatas[index];
                let title = $A.trim(taskData.title);
                if ($A.count(title) == 0) {
                    return;
                }
                this.$set(taskData, 'focus', false);
                this.$set(taskData, 'title', '');
                //
                let tempId = $A.randomString(16);
                taskData.lists.unshift({
                    id: tempId,
                    title: title,
                    loadIng: true,
                });
                this.taskSortDisabled = true;
                $A.aAjax({
                    url: 'project/task/add',
                    data: {
                        title: title,
                        level: index,
                    },
                    complete: () => {
                        this.taskSortDisabled = false;
                    },
                    error: () => {
                        taskData.lists.some((item, i) => {
                            if (item.id == tempId) {
                                taskData.lists.splice(i, 1);
                                return true;
                            }
                        });
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.$Message.success(res.msg);
                        } else {
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                        }
                        taskData.lists.some((item, i) => {
                            if (item.id == tempId) {
                                if (res.ret === 1) {
                                    taskData.lists.splice(i, 1, res.data);
                                } else {
                                    taskData.lists.splice(i, 1);
                                }
                                return true;
                            }
                        });
                        this.taskSortData = this.getTaskSort();
                    }
                });
            },

            getTaskSort() {
                let sortData = "",
                    taskData = "";
                for (let level in this.taskDatas) {
                    taskData = "";
                    this.taskDatas[level].lists.forEach((task) => {
                        if (taskData) taskData += "-";
                        taskData += task.id;
                    });
                    if (sortData) sortData += ";";
                    sortData += level + ":" + taskData;
                }
                return sortData;
            },

            handleTodo(event) {
                switch (event) {
                    case 'calendar':
                    case 'complete':
                    case 'attention': {
                        this.todoDrawerShow = true;
                        this.todoDrawerTab = event;
                        break;
                    }
                    case 'report': {
                        this.todoReportDrawerShow = true;
                        break;
                    }
                }
            },

            taskSortUpdate() {
                let oldSort = this.taskSortData;
                let newSort = this.getTaskSort();
                if (oldSort == newSort) {
                    return;
                }
                this.taskSortData = newSort;
                this.taskSortDisabled = true;
                $A.aAjax({
                    url: 'project/sort/todo',
                    data: {
                        oldsort: oldSort,
                        newsort: newSort,
                    },
                    complete: () => {
                        this.taskSortDisabled = false;
                    },
                    error: () => {
                        this.refreshTask();
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.$Message.success(res.msg);
                            $A.triggerTaskInfoListener('tasklevel', res.data.taskLevel);
                        } else {
                            this.refreshTask();
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                        }
                    }
                });
            },

            openTaskModal(taskDetail) {
                this.taskDetail(taskDetail);
            },
        },
    }
</script>
