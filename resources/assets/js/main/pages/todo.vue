<template>
    <div class="w-main todo">

        <v-title>{{$L('待办')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="todo"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <i class="ft icon">&#xE787;</i> {{$L('我的待办')}}
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover"><i class="ft icon">&#xE706;</i> {{$L('待办日程')}}</span>
                    <span class="ft hover"><i class="ft icon">&#xE73D;</i> {{$L('已完成的任务')}}</span>
                    <span class="ft hover"><i class="ft icon">&#xE748;</i> {{$L('我关注的任务')}}</span>
                    <span class="ft hover"><i class="ft icon">&#xE743;</i> {{$L('周报/日报')}}</span>
                </div>
            </div>
        </div>

        <w-content>
            <div class="todo-main">
                <ul v-for="subs in [['1', '2'], ['3', '4']]">
                    <li v-for="index in subs">
                        <div class="todo-card">
                            <div class="todo-card-head" :class="['p' + index]">
                                <i class="ft icon flag">&#xE753;</i>
                                <div class="todo-card-title">{{$L(pTitle(index))}}</div>
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
                                <ul v-if="taskDatas[index].lists.length > 0">
                                    <li v-for="task in taskDatas[index].lists" :key="task.id" :class="{complete:task.complete}">
                                        <Icon v-if="task.complete" class="task-check" type="md-checkbox-outline" />
                                        <Icon v-else class="task-check" type="md-square-outline" />
                                        <div v-if="task.overdue" class="task-overdue">[超期]</div>
                                        <div class="task-title">{{task.title}}</div>
                                    </li>
                                    <li v-if="taskDatas[index].hasMorePages === true" class="more" @click="getTask(index, true)">加载更多</li>
                                </ul>
                                <div v-else-if="taskDatas[index].loadIng == 0" class="content-empty">{{$L('恭喜你！已完成了所有待办')}}</div>
                                <div v-if="taskDatas[index].loadIng > 0" class="content-loading"><w-loading></w-loading></div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </w-content>

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
            ul {
                flex: 1;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                width: 100%;
                li {
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
                            ul {
                                display: flex;
                                flex-direction: column;
                                li {
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
                                        padding-top: 2px;
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
                ul {
                    flex-direction: column;
                    li {
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
    import WHeader from "../components/WHeader";
    import WContent from "../components/WContent";
    import WLoading from "../components/WLoading";
    export default {
        components: {WContent, WHeader, WLoading},
        data () {
            return {
                userInfo: {},

                taskDatas: {
                    "1": {lists: [], hasMorePages: false},
                    "2": {lists: [], hasMorePages: false},
                    "3": {lists: [], hasMorePages: false},
                    "4": {lists: [], hasMorePages: false},
                },
            }
        },
        mounted() {
            this.userInfo = $A.getUserInfo((res) => {
                this.userInfo = res;
            });
            for (let i = 1; i <= 4; i++) {
                this.getTask(i.toString());
            }
        },
        computed: {

        },
        watch: {

        },
        methods: {
            pTitle(p) {
                switch (p) {
                    case "1":
                        return "重要且紧急";
                    case "2":
                        return "重要不紧急";
                    case "3":
                        return "紧急不重要";
                    case "4":
                        return "不重要不紧急";
                }
            },

            getTask(index, isNext) {
                let taskData = this.taskDatas[index];
                let idlater = 0;
                if (isNext === true) {
                    if (taskData.hasMorePages !== true) {
                        return;
                    }
                    idlater = taskData.lists[taskData.lists.length - 1].id
                }
                this.$set(taskData, 'hasMorePages', false);
                this.$set(taskData, 'loadIng', $A.runNum(taskData.loadIng) + 1);
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        level: index,
                        idlater: idlater,
                        page: 1,
                        pagesize: 10,
                    },
                    complete: () => {
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
                            this.$set(taskData, 'hasMorePages', res.data.hasMorePages);
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
                $A.aAjax({
                    url: 'project/task/add',
                    data: {
                        title: title,
                        level: index,
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
                    }
                });
            }
        },
    }
</script>
