<template>
    <div class="w-main project-panel">

        <v-title>{{$L('项目面板')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="project"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <div class="project-title">
                        <div v-if="loadIng > 0" class="project-title-loading"><w-loading></w-loading></div>
                        <h1>{{projectDetail.title}}</h1>
                    </div>
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover"><i class="ft icon">&#xE6E7;</i> 任务看板</span>
                    <span class="ft hover"><i class="ft icon">&#xE89E;</i> 任务列表</span>
                    <span class="ft hover"><i class="ft icon">&#xE705;</i> 甘特图</span>
                    <span class="ft hover"><i class="ft icon">&#xE7A7;</i> 设置</span>
                </div>
            </div>
        </div>

        <w-content>
            <draggable v-if="projectLabel.length > 0" v-model="projectLabel" class="label-box" draggable=".label-draggable" :animation="150">
                <div v-for="label in projectLabel" :key="label.id" class="label-item label-draggable">
                    <div class="label-body">
                        <div class="title-box">
                            <h2>{{label.title}}</h2>
                            <Dropdown trigger="click" transfer>
                                <Icon type="ios-more"/>
                                <DropdownMenu slot="list">
                                    <DropdownItem>重命名</DropdownItem>
                                    <DropdownItem>删除</DropdownItem>
                                </DropdownMenu>
                            </Dropdown>
                        </div>
                        <draggable v-model="label.taskLists" class="task-box" group="task" :sort="false" :animation="150" draggable=".task-draggable">
                            <div v-for="task in label.taskLists" :key="task.id" class="task-item task-draggable" :class="['p'+task.level,task.complete?'complete':'',task.overdue?'overdue':'']">
                                <div class="task-title">{{task.title}}</div>
                                <div class="task-more">
                                    <div v-if="task.overdue" class="task-status">已超期</div>
                                    <div v-else-if="task.complete" class="task-status">已完成</div>
                                    <div v-else class="task-status">未完成</div>
                                    <Tooltip class="task-userimg" :content="task.nickname || task.username"><img :src="task.userimg"/></Tooltip>
                                </div>
                            </div>
                            <div slot="footer">
                                <project-add-task :placeholder='`添加任务至"${label.title}"`' :projectid="label.projectid" :labelid="label.id" @on-add-success="addTaskSuccess(label)"></project-add-task>
                            </div>
                        </draggable>
                    </div>
                </div>
                <div v-if="loadDetailed" slot="footer" class="label-item label-create">
                    <div class="label-body">
                        <div class="trigger-box ft hover"><i class="ft icon">&#xE8C8;</i>添加一个新列表</div>
                    </div>
                </div>
            </draggable>
        </w-content>

    </div>
</template>

<style lang="scss">
    #project-panel-enter-textarea {
        background: transparent;
        background: none;
        outline: none;
        border: 0;
        resize: none;
        padding: 0;
        margin: 8px 0;
        line-height: 22px;
        border-radius: 0;
        color: rgba(0, 0, 0, 0.85);
        &:focus {
            border-color: transparent;
            box-shadow: none;
        }
    }
</style>
<style lang="scss" scoped>
    .project-panel {
        .project-title {
            display: flex;
            flex-direction: row;
            align-items: center;
            .project-title-loading {
                width: 18px;
                height: 18px;
                margin-right: 6px;
                display: flex;
            }
            h1 {
                font-size: 14px;
                font-weight: 500;
            }
        }
        .label-box {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: flex-start;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            height: 100%;
            padding: 15px;
            .label-item {
                flex-grow: 1;
                flex-shrink: 0;
                flex-basis: auto;
                height: 100%;
                padding-right: 15px;
                &.label-create {
                    cursor: pointer;
                }
                .label-body {
                    width: 300px;
                    height: 100%;
                    border-radius: 0.15rem;
                    background-color: #ebecf0;
                    overflow: hidden;
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    .title-box {
                        padding: 0 12px;
                        font-weight: bold;
                        color: #666666;
                        position: relative;
                        cursor: move;
                        display: flex;
                        align-items: center;
                        width: 100%;
                        height: 42px;
                        h2 {
                            flex: 1;
                            font-size: 16px;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        }
                        i {
                            font-weight: 500;
                            font-size: 18px;
                            height: 100%;
                            line-height: 42px;
                            width: 42px;
                            text-align: right;
                            cursor: pointer;
                        }
                    }
                    .task-box {
                        flex: 1;
                        width: 100%;
                        overflow: auto;
                        display: flex;
                        flex-direction: column;
                        padding: 0 12px 2px;
                        .task-item {
                            width: 100%;
                            margin: 5px 0 8px;
                            padding: 8px;
                            background-color: #ffffff;
                            border-left: 2px solid #BF9F03;
                            border-right: 2px solid #ffffff;
                            color: #091e42;
                            border-radius: 3px;
                            box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
                            transition: all 0.2s;
                            cursor: pointer;
                            &:hover{
                                box-shadow: 0 0 4px 0 rgba(0, 0, 0, 0.38);
                            }
                            &.p1 {
                                border-left-color: #ff0000;
                            }
                            &.p2 {
                                border-left-color: #BB9F35;
                            }
                            &.p3 {
                                border-left-color: #449EDD;
                            }
                            &.p4 {
                                border-left-color: #84A83B;
                            }
                            &.complete {
                                .task-more {
                                    .task-status {
                                        color: #666666;
                                    }
                                }
                            }
                            &.overdue {
                                .task-more {
                                    .task-status {
                                        color: #ff0000;
                                    }
                                }
                            }
                            .task-title {
                                font-size: 12px;
                                color: #091e42;
                            }
                            .task-more {
                                min-height: 30px;
                                display: flex;
                                align-items: flex-end;
                                .task-status {
                                    color: #19be6b;
                                    font-size: 12px;
                                    flex: 1;
                                }
                                .task-userimg {
                                    width: 26px;
                                    height: 26px;
                                    img {
                                        object-fit: cover;
                                        width: 100%;
                                        height: 100%;
                                        border-radius: 50%;
                                    }
                                }
                            }
                        }
                    }
                    .trigger-box {
                        text-align: center;
                        font-size: 16px;
                        color: #666;
                        width: 100%;
                        position: absolute;
                        top: 50%;
                        transform: translate(0, -50%);
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
    import ProjectAddTask from "../components/project/task/add";

    export default {
        components: {ProjectAddTask, draggable, WLoading, WContent, WHeader},
        data () {
            return {
                loadIng: 0,
                loadDetailed: false,

                projectid: this.$route.params.id,
                projectDetail: {},
                projectLabel: [],
            }
        },
        mounted() {
            if ($A.runNum(this.projectid) <= 0) {
                this.goBack();
                return;
            }
            this.getDetail();
        },
        computed: {

        },
        watch: {

        },
        methods: {
            getDetail() {
                this.loadIng++;
                $A.aAjax({
                    url: 'project/detail',
                    data: {
                        projectid: this.projectid,
                    },
                    complete: () => {
                        this.loadIng--;
                        this.loadDetailed = true;
                    },
                    error: () => {
                        this.$Message.error(this.$L('网络繁忙，请稍后再试！'));
                        this.goBack();
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.projectDetail = res.data.project;
                            this.projectLabel = res.data.label;
                        } else {
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                            this.goBack();
                        }
                    }
                });
            },
            addTaskSuccess(label) {
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        projectid: this.projectid,
                        labelid: label.id,
                    },
                    complete: () => {
                    },
                    error: () => {
                        window.location.reload();
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.$set(label, 'taskLists', res.data.lists);
                        } else {
                            window.location.reload();
                        }
                    }
                });
            }
        },
    }
</script>
