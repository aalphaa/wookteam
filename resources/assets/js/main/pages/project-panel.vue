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
                    <span class="ft hover" @click="openProjectDrawer('lists')"><i class="ft icon">&#xE89E;</i> 任务列表</span>
                    <span class="ft hover" @click="openProjectDrawer('files')"><i class="ft icon">&#xE701;</i> 文件列表</span>
                    <span class="ft hover" @click="openProjectDrawer('logs')"><i class="ft icon">&#xE753;</i> 项目动态</span>
                    <span class="ft hover" @click="openProjectDrawer('setting')"><i class="ft icon">&#xE7A7;</i> 设置</span>
                </div>
            </div>
        </div>

        <w-content>
            <draggable v-if="projectLabel.length > 0" v-model="projectLabel" class="label-box" draggable=".label-draggable" :animation="150">
                <div v-for="label in projectLabel" :key="label.id" class="label-item label-draggable">
                    <div class="label-body">
                        <div class="title-box">
                            <div v-if="label.loadIng === true" class="title-loading">
                                <w-loading></w-loading>
                            </div>
                            <h2>{{label.title}}</h2>
                            <Dropdown trigger="click" @on-click="handleLabel($event, label)" transfer>
                                <Icon type="ios-more"/>
                                <DropdownMenu slot="list">
                                    <Dropdown-item name="rename">{{$L('重命名')}}</Dropdown-item>
                                    <Dropdown-item name="delete">{{$L('删除')}}</Dropdown-item>
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
                <div v-if="loadDetailed" slot="footer" class="label-item label-create" @click="addLabel">
                    <div class="label-body">
                        <div class="trigger-box ft hover"><i class="ft icon">&#xE8C8;</i>添加一个新列表</div>
                    </div>
                </div>
            </draggable>
        </w-content>

        <Drawer v-model="projectDrawerShow" width="80%">
            <Tabs v-if="projectDrawerShow" v-model="projectDrawerTab">
                <TabPane :label="$L('任务列表')" name="lists">
                    <project-task-lists :canload="projectDrawerShow && projectDrawerTab == 'lists'" :projectid="projectid" :labelLists="projectSimpleLabel"></project-task-lists>
                </TabPane>
                <TabPane :label="$L('文件列表')" name="files">
                </TabPane>
                <TabPane :label="$L('项目动态')" name="logs">
                </TabPane>
                <TabPane :label="$L('项目设置')" name="setting">
                </TabPane>
            </Tabs>
        </Drawer>
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
                    &:hover {
                        .trigger-box {
                            transform: translate(0, -50%) scale(1.1);
                        }
                    }
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
                        .title-loading {
                            width: 16px;
                            height: 16px;
                            margin-right: 6px;
                        }
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
                        transform: translate(0, -50%) scale(1);
                        transition: all 0.2s;
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
    import ProjectTaskLists from "../components/project/task/lists";

    export default {
        components: {ProjectTaskLists, ProjectAddTask, draggable, WLoading, WContent, WHeader},
        data () {
            return {
                loadIng: 0,
                loadDetailed: false,

                projectid: 0,
                projectDetail: {},
                projectLabel: [],
                projectSimpleLabel: [],

                projectDrawerShow: false,
                projectDrawerTab: 'lists',
            }
        },
        mounted() {

        },
        activated() {
            this.projectid = this.$route.params.projectid;
            if (typeof this.$route.params.other === "object") {
                this.$set(this.projectDetail, 'title', $A.getObject(this.$route.params.other, 'title'))
            }
        },
        watch: {
            projectid(val) {
                if ($A.runNum(val) <= 0) {
                    this.goBack();
                    return;
                }
                this.projectDetail = {};
                this.projectLabel = [];
                this.projectSimpleLabel = [];
                this.getDetail();
            }
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
                            this.projectSimpleLabel = res.data.simpleLabel;
                        } else {
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                            this.goBack();
                        }
                    }
                });
            },
            handleLabel(event, labelDetail) {
                switch (event) {
                    case 'rename': {
                        this.renameLabel(labelDetail);
                        break;
                    }
                    case 'delete': {
                        this.deleteLabel(labelDetail);
                        break;
                    }
                }
            },

            renameLabel(item) {
                this.renameValue = "";
                this.$Modal.confirm({
                    render: (h) => {
                        return h('div', [
                            h('div', {
                                style: {
                                    fontSize: '16px',
                                    fontWeight: '500',
                                    marginBottom: '20px',
                                }
                            }, '重命名列表'),
                            h('Input', {
                                props: {
                                    value: this.renameValue,
                                    autofocus: true,
                                    placeholder: '请输入新的列表名称'
                                },
                                on: {
                                    input: (val) => {
                                        this.renameValue = val;
                                    }
                                }
                            })
                        ])
                    },
                    loading: true,
                    onOk: () => {
                        if (this.renameValue) {
                            this.$set(item, 'loadIng', true);
                            let title = this.renameValue;
                            $A.aAjax({
                                url: 'project/label/rename',
                                data: {
                                    projectid: this.projectid,
                                    labelid: item.id,
                                    title: title,
                                },
                                complete: () => {
                                    this.$set(item, 'loadIng', false);
                                },
                                error: () => {
                                    this.$Modal.remove();
                                    this.$Message.error(this.$L('网络繁忙，请稍后再试！'));
                                },
                                success: (res) => {
                                    this.$Modal.remove();
                                    this.$set(item, 'title', title);
                                    setTimeout(() => {
                                        if (res.ret === 1) {
                                            this.$Message.success(res.msg);
                                        } else {
                                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                        }
                                    }, 350);
                                }
                            });
                        } else {
                            this.$Modal.remove();
                        }
                    },
                });
            },

            deleteLabel(item) {
                let redTip = item.taskLists.length > 0 ? '<div style="color:red;font-weight:500">注：将同时删除列表下所有任务</div>' : '';
                this.$Modal.confirm({
                    title: '删除列表',
                    content: '<div>你确定要删除此列表吗？</div>' + redTip,
                    loading: true,
                    onOk: () => {
                        $A.aAjax({
                            url: 'project/label/delete',
                            data: {
                                projectid: this.projectid,
                                labelid: item.id,
                            },
                            error: () => {
                                this.$Modal.remove();
                                this.$Message.error(this.$L('网络繁忙，请稍后再试！'));
                            },
                            success: (res) => {
                                this.$Modal.remove();
                                this.projectLabel.some((label, index) => {
                                    if (label.id == item.id) {
                                        this.projectLabel.splice(index, 1);
                                        return true;
                                    }
                                });
                                setTimeout(() => {
                                    if (res.ret === 1) {
                                        this.$Message.success(res.msg);
                                    } else {
                                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                    }
                                }, 350);
                            }
                        });
                    }
                });
            },

            addLabel() {
                this.labelValue = "";
                this.$Modal.confirm({
                    render: (h) => {
                        return h('div', [
                            h('div', {
                                style: {
                                    fontSize: '16px',
                                    fontWeight: '500',
                                    marginBottom: '20px',
                                }
                            }, '添加列表'),
                            h('Input', {
                                props: {
                                    value: this.labelValue,
                                    autofocus: true,
                                    placeholder: '请输入列表名称'
                                },
                                on: {
                                    input: (val) => {
                                        this.labelValue = val;
                                    }
                                }
                            })
                        ])
                    },
                    loading: true,
                    onOk: () => {
                        if (this.labelValue) {
                            $A.aAjax({
                                url: 'project/label/add',
                                data: {
                                    projectid: this.projectid,
                                    title: this.labelValue
                                },
                                error: () => {
                                    this.$Modal.remove();
                                    this.$Message.error(this.$L('网络繁忙，请稍后再试！'));
                                },
                                success: (res) => {
                                    this.$Modal.remove();
                                    this.projectLabel.push(res.data);
                                    setTimeout(() => {
                                        if (res.ret === 1) {
                                            this.$Message.success(res.msg);
                                        } else {
                                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                        }
                                    }, 350);
                                }
                            });
                        } else {
                            this.$Modal.remove();
                        }
                    },
                });
            },
            addTaskSuccess(label) {
                this.$set(label, 'loadIng', true);
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        projectid: this.projectid,
                        labelid: label.id,
                        levelsort: 1
                    },
                    complete: () => {
                        this.$set(label, 'loadIng', false);
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
            },

            openProjectDrawer(tab) {
                this.projectDrawerTab = tab;
                this.projectDrawerShow = true;
            }
        },
    }
</script>
