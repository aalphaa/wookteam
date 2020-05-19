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
                        <div class="project-title-refresh" @click="getDetail(true)">刷新</div>
                    </div>
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover" @click="openProjectDrawer('lists')"><i class="ft icon">&#xE89E;</i> 任务列表</span>
                    <span class="ft hover" @click="openProjectDrawer('files')"><i class="ft icon">&#xE701;</i> 文件列表</span>
                    <span class="ft hover" @click="openProjectDrawer('logs')"><i class="ft icon">&#xE753;</i> 项目动态</span>
                    <span class="ft hover" @click="openProjectSettingDrawer('archived')"><i class="ft icon">&#xE7A7;</i> 设置</span>
                </div>
            </div>
        </div>

        <w-content>
            <draggable
                v-if="projectLabel.length > 0"
                v-model="projectLabel"
                class="label-box"
                draggable=".label-draggable"
                :animation="150"
                :disabled="projectSortDisabled"
                @sort="projectSortUpdate(true)">
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
                                    <Dropdown-item name="refresh">{{$L('刷新列表')}}</Dropdown-item>
                                    <Dropdown-item name="rename">{{$L('重命名')}}</Dropdown-item>
                                    <Dropdown-item name="delete">{{$L('删除')}}</Dropdown-item>
                                </DropdownMenu>
                            </Dropdown>
                        </div>
                        <draggable
                            v-model="label.taskLists"
                            class="task-box"
                            group="task"
                            draggable=".task-draggable"
                            :animation="150"
                            :disabled="projectSortDisabled"
                            @sort="projectSortUpdate(false)"
                            @remove="projectSortUpdate(false)">
                            <div v-for="task in label.taskLists" :key="task.id" class="task-item task-draggable">
                                <div class="task-shadow" :class="[
                                        'p'+task.level,
                                        task.complete ? 'complete' : '',
                                        task.overdue ? 'overdue' : '',
                                        task.isNewtask === true ? 'newtask' : ''
                                    ]">
                                    <div class="task-title">{{task.title}}</div>
                                    <div class="task-more">
                                        <div v-if="task.overdue" class="task-status">已超期</div>
                                        <div v-else-if="task.complete" class="task-status">已完成</div>
                                        <div v-else class="task-status">未完成</div>
                                        <Tooltip class="task-userimg" :content="task.nickname || task.username" transfer><img :src="task.userimg"/></Tooltip>
                                    </div>
                                </div>
                            </div>
                            <div slot="footer">
                                <project-add-task :placeholder='`添加任务至"${label.title}"`' :projectid="label.projectid" :labelid="label.id" @on-add-success="addTaskSuccess($event, label)"></project-add-task>
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
                    <project-task-files :canload="projectDrawerShow && projectDrawerTab == 'files'" :projectid="projectid"></project-task-files>
                </TabPane>
                <TabPane :label="$L('项目动态')" name="logs">
                    <project-task-logs :canload="projectDrawerShow && projectDrawerTab == 'logs'" :projectid="projectid"></project-task-logs>
                </TabPane>
            </Tabs>
        </Drawer>

        <Drawer v-model="projectSettingDrawerShow" width="75%">
            <Tabs v-if="projectSettingDrawerShow" v-model="projectSettingDrawerTab">
                <TabPane :label="$L('已归档任务')" name="archived">
                    <project-archived :canload="projectSettingDrawerShow && projectSettingDrawerTab == 'archived'" :projectid="projectid"></project-archived>
                </TabPane>
                <TabPane :label="$L('成员管理')" name="member">
                    <project-users :canload="projectSettingDrawerShow && projectSettingDrawerTab == 'member'" :projectid="projectid"></project-users>
                </TabPane>
                <TabPane :label="$L('项目统计')" name="statistics">
                    <project-statistics :canload="projectSettingDrawerShow && projectSettingDrawerTab == 'statistics'" :projectid="projectid"></project-statistics>
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
            &:hover {
                .project-title-refresh {
                    display: block;
                }
            }
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
            .project-title-refresh {
                display: none;
                padding-left: 12px;
                padding-right: 12px;
                color: #048be0;
                cursor: pointer;
                &:hover {
                    text-decoration: underline;
                }
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
            transform: translateZ(0);
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
                        transform: translateZ(0);
                        .task-item {
                            width: 100%;
                            .task-shadow {
                                margin: 5px 0 4px;
                                padding: 8px;
                                background-color: #ffffff;
                                border-left: 2px solid #BF9F03;
                                border-right: 2px solid #ffffff;
                                color: #091e42;
                                border-radius: 3px;
                                cursor: pointer;
                                box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
                                transition: all 0.3s;
                                transform: scale(1);
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
                                    .task-title {
                                        font-weight: bold;
                                    }
                                    .task-more {
                                        .task-status {
                                            color: #ff0000;
                                        }
                                    }
                                }
                                &.newtask {
                                    transform: scale(1.5);
                                }
                                .task-title {
                                    font-size: 12px;
                                    color: #091e42;
                                    word-break: break-all;
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
                    }
                    .trigger-box {
                        text-align: center;
                        font-size: 16px;
                        color: #666;
                        width: 100%;
                        position: absolute;
                        top: 50%;
                        transform: translate(0, -50%) scale(1);
                        transition: all 0.3s;
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
    import ProjectTaskFiles from "../components/project/task/files";
    import ProjectTaskLogs from "../components/project/task/logs";
    import ProjectArchived from "../components/project/archived";
    import ProjectUsers from "../components/project/users";
    import ProjectStatistics from "../components/project/statistics";

    export default {
        components: {
            ProjectStatistics,
            ProjectUsers,
            ProjectArchived,
            ProjectTaskLogs,
            ProjectTaskFiles, ProjectTaskLists, ProjectAddTask, draggable, WLoading, WContent, WHeader},
        data () {
            return {
                loadIng: 0,
                loadDetailed: false,

                projectid: 0,
                projectDetail: {},
                projectLabel: [],
                projectSimpleLabel: [],
                projectSortData: '',
                projectSortDisabled: false,

                projectDrawerShow: false,
                projectDrawerTab: 'lists',

                projectSettingDrawerShow: false,
                projectSettingDrawerTab: 'archived',
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
            getDetail(successTip) {
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
                        this.goBack();
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.projectDetail = res.data.project;
                            this.projectLabel = res.data.label;
                            this.projectSimpleLabel = res.data.simpleLabel;
                            this.projectSortData = this.getProjectSort();
                            if (successTip === true) {
                                this.$Message.success(this.$L('刷新成功！'));
                            }
                        } else {
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                            this.goBack();
                        }
                    }
                });
            },
            getProjectSort() {
                let sortData = "",
                    taskData = "";
                this.projectLabel.forEach((label) => {
                    taskData = "";
                    label.taskLists.forEach((task) => {
                        if (taskData) taskData+= "-";
                        taskData+= task.id;
                    });
                    if (sortData) sortData+= ";";
                    sortData+= label.id + ":" + taskData;
                });
                return sortData;
            },
            handleLabel(event, labelDetail) {
                switch (event) {
                    case 'refresh': {
                        this.refreshLabel(labelDetail);
                        break;
                    }
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

            refreshLabel(item) {
                this.$set(item, 'loadIng', true);
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        projectid: this.projectid,
                        labelid: item.id,
                    },
                    complete: () => {
                        this.$set(item, 'loadIng', false);
                    },
                    error: () => {
                        window.location.reload();
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.$set(item, 'taskLists', res.data.lists);
                        } else {
                            window.location.reload();
                        }
                    }
                });
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
                                    alert(this.$L('网络繁忙，请稍后再试！'));
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
                                alert(this.$L('网络繁忙，请稍后再试！'));
                            },
                            success: (res) => {
                                this.$Modal.remove();
                                this.projectLabel.some((label, index) => {
                                    if (label.id == item.id) {
                                        this.projectLabel.splice(index, 1);
                                        this.projectSortData = this.getProjectSort();
                                        return true;
                                    }
                                });
                                setTimeout(() => {
                                    if (res.ret === 1) {
                                        this.$Message.success(res.msg);
                                        $A.triggerTaskInfoListener('deletelabel', item.id);
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
                                    alert(this.$L('网络繁忙，请稍后再试！'));
                                },
                                success: (res) => {
                                    this.$Modal.remove();
                                    this.projectLabel.push(res.data);
                                    this.projectSortData = this.getProjectSort();
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

            addTaskSuccess(taskDetail, label) {
                if (label.taskLists instanceof Array) {
                    taskDetail.isNewtask = true;
                    label.taskLists.unshift(taskDetail);
                    this.$nextTick(() => {
                        this.$set(taskDetail, 'isNewtask', false);
                    });
                } else {
                    this.refreshLabel(label);
                }
            },

            openProjectDrawer(tab) {
                this.projectDrawerTab = tab;
                this.projectDrawerShow = true;
            },

            openProjectSettingDrawer(tab) {
                this.projectSettingDrawerTab = tab;
                this.projectSettingDrawerShow = true;
            },

            projectSortUpdate(isLabel) {
                let oldSort = this.projectSortData;
                let newSort = this.getProjectSort();
                if (oldSort == newSort) {
                    return;
                }
                this.projectSortData = newSort;
                this.projectSortDisabled = true;
                this.loadIng++;
                $A.aAjax({
                    url: 'project/sort',
                    data: {
                        projectid: this.projectid,
                        oldsort: oldSort,
                        newsort: newSort,
                        label: isLabel === true ? 1 : 0
                    },
                    complete: () => {
                        this.projectSortDisabled = false;
                        this.loadIng--;
                    },
                    error: () => {
                        this.getDetail();
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.$Message.success(res.msg);
                        } else {
                            this.getDetail();
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                        }
                    }
                });
            },
        },
    }
</script>
