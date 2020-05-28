<template>
    <div class="project-task-detail-window" :class="{'task-detail-show': visible}">
        <div class="task-detail-main">
            <div class="detail-left">
                <div class="detail-title-box detail-icon">
                    <input v-model="detail.title" :disabled="!!loadData.title" type="text" maxlength="60" @keydown.enter="(e)=>{e.target.blur()}" @blur="handleTask('title')">
                    <div class="time">
                        <span class="z-nick">{{detail.createuser}}</span>
                        {{$L('创建于：')}}
                        <span>{{$A.formatDate("Y-m-d H:i:s", detail.indate)}}</span>
                    </div>
                </div>
                <div class="detail-desc-box detail-icon">
                    <div class="detail-h2"><strong class="active">{{$L('描述')}}</strong></div>
                    <textarea v-model="detail.desc" :placeholder="$L('添加详细描述...')"  @keydown="descKeydown" @blur="handleTask('desc')"></textarea>
                </div>
                <ul class="detail-text-box">
                    <li v-if="detail.startdate > 0 && detail.enddate > 0" class="text-time detail-icon">
                        <span>{{$L('计划时间：')}}</span>
                        <em>{{$A.formatDate("Y-m-d H:i", detail.startdate)}} {{$L('至')}} {{$A.formatDate("Y-m-d H:i", detail.enddate)}}</em>
                        <em v-if="detail.overdue" class="overdue">[{{$L('已超期')}}]</em>
                    </li>
                    <li class="text-username detail-icon">
                        <span>{{$L('负责人：')}}</span>
                        <em>{{detail.username}}</em>
                    </li>
                    <li v-if="followerLength(detail.follower) > 0" class="text-follower detail-icon">
                        <span>{{$L('关注者：')}}</span>
                        <em>
                            <Tag v-for="(fname, findex) in detail.follower" :key="findex" closable @on-close="handleTask('unattention', {username:fname,uisynch:true})">{{fname}}</Tag>
                        </em>
                    </li>
                    <li class="text-level detail-icon">
                        <span>{{$L('优先级：')}}</span>
                        <em :class="`p${detail.level}`">{{levelFormt(detail.level)}}</em>
                    </li>
                    <li class="text-status detail-icon">
                        <span>{{$L('任务状态：')}}</span>
                        <em v-if="detail.complete" class="complete">{{$L('已完成')}}</em>
                        <em v-else class="unfinished">{{$L('未完成')}}</em>
                    </li>
                </ul>
                <div :style="`${detail.filenum>0?'':'display:none'}`">
                    <div class="detail-h2 detail-file-box detail-icon"><strong class="active">{{$L('附件')}}</strong></div>
                    <project-task-files ref="upload" :taskid="taskid" :simple="true" @change="handleTask('filechange', $event)"></project-task-files>
                </div>
                <div class="detail-h2 detail-comment-box detail-icon"><strong class="link" :class="{active:logType=='评论'}" @click="logType='评论'">{{$L('评论')}}</strong><em></em><strong class="link" :class="{active:logType=='日志'}" @click="logType='日志'">{{$L('操作记录')}}</strong></div>
                <div class="detail-log-box">
                    <project-task-logs ref="log" :logtype="logType" :projectid="detail.projectid" :taskid="taskid" :pagesize="5"></project-task-logs>
                </div>
                <div class="detail-footer-box">
                    <Input class="comment-input" v-model="commentText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" @on-keydown="commentKeydown" :placeholder="$L('输入评论，Enter发表评论，Shift+Enter换行')" />
                    <Button :loading="!!loadData.comment" :disabled="!commentText" type="primary" @click="handleTask('comment')">评 论</Button>
                </div>
            </div>
            <div class="detail-right">
                <div class="cancel"><em @click="visible=false"></em></div>
                <Dropdown trigger="click" class="block" @on-click="handleTask">
                    <Button :loading="!!loadData.unfinished || !!loadData.complete" icon="md-checkmark-circle-outline" class="btn">{{$L('标记')}}{{$L(detail.complete?'未完成':'已完成')}}</Button>
                    <DropdownMenu slot="list">
                        <DropdownItem name="unfinished">{{$L('标记未完成')}}<Icon v-if="!detail.complete" type="md-checkmark" class="checkmark"/></DropdownItem>
                        <DropdownItem name="complete">{{$L('标记已完成')}}<Icon v-if="detail.complete" type="md-checkmark" class="checkmark"/></DropdownItem>
                        <DropdownItem name="archived2">{{$L('完成并归档')}}<Icon v-if="detail.complete && detail.archived" type="md-checkmark" class="checkmark"/></DropdownItem>
                    </DropdownMenu>
                </Dropdown>
                <Dropdown trigger="click" class="block" @on-click="handleTask">
                    <Button :loading="!!loadData.level" icon="md-funnel" class="btn">{{$L('优先级')}}</Button>
                    <DropdownMenu slot="list">
                        <DropdownItem v-for="level in [1,2,3,4]" :key="level" :name="`level-${level}`" :class="`p${level}`">{{levelFormt(level)}}<Icon v-if="detail.level==level" type="md-checkmark" class="checkmark"/></DropdownItem>
                    </DropdownMenu>
                </Dropdown>
                <Poptip placement="bottom" class="block">
                    <Button :loading="!!loadData.username" icon="md-person" class="btn">{{$L('负责人')}}</Button>
                    <div slot="content">
                        <div style="width:280px">
                            {{$L('选择负责人')}}
                            <UseridInput :projectid="detail.projectid" :nousername="detail.username" :transfer="false" @change="handleTask('usernameb', $event)" :placeholder="$L('输入关键词搜索')" style="margin:5px 0 3px"></UseridInput>
                        </div>
                    </div>
                </Poptip>
                <Poptip ref="timeRef" placement="bottom" class="block" @on-popper-show="handleTask('inittime')">
                    <Button :loading="!!loadData.plannedtime || !!loadData.unplannedtime" icon="md-calendar" class="btn">{{$L('计划时间')}}</Button>
                    <div slot="content">
                        <div style="width:280px">
                            {{$L('选择日期范围')}}
                            <Date-picker
                                v-model="timeValue"
                                :options="timeOptions"
                                :placeholder="$L('日期范围')"
                                format="yyyy-MM-dd HH:mm"
                                type="datetimerange"
                                placement="bottom"
                                @on-ok="handleTask('plannedtimeb')"
                                @on-clear="handleTask('unplannedtimeb')"
                                style="display:block;margin:5px 0 3px"></Date-picker>
                        </div>
                    </div>
                </Poptip>
                <Button icon="md-attach" class="btn" @click="handleTask('fileupload')">{{$L('添加附件')}}</Button>
                <Poptip ref="attentionRef" v-if="detail.username == myUsername" placement="bottom" class="block" @on-popper-show="() => {$set(detail, 'attentionLists', followerToStr(detail.follower))}">
                    <Button :loading="!!loadData.attention" icon="md-at" class="btn">{{$L('关注人')}}</Button>
                    <div slot="content">
                        <div style="width:280px">
                            {{$L('选择关注人')}}
                            <UseridInput :multiple="true" :transfer="false" v-model="detail.attentionLists" :placeholder="$L('输入关键词搜索')" style="margin:5px 0 3px"></UseridInput>
                            <Button :loading="!!loadData.attention" :disabled="!detail.attentionLists" class="btn" type="primary" style="text-align:center;width:72px;height:28px;font-size:13px" @click="handleTask('attention')">确 定</Button>
                        </div>
                    </div>
                </Poptip>
                <Button v-else-if="haveAttention(detail.follower)" :loading="!!loadData.unattention" icon="md-at" class="btn" @click="handleTask('unattention', {username:myUsername})">{{$L('取消关注')}}</Button>
                <Button v-else :loading="!!loadData.attention" icon="md-at" class="btn" @click="handleTask('attentiona')">{{$L('关注任务')}}</Button>
                <Button v-if="!detail.archived" :loading="!!loadData.archived" icon="md-filing" class="btn" @click="handleTask('archived')">{{$L('归档')}}</Button>
                <Button v-else :loading="!!loadData.unarchived" icon="md-filing" class="btn" @click="handleTask('unarchived')">{{$L('取消归档')}}</Button>
                <Button :loading="!!loadData.delete" icon="md-trash" class="btn" type="error" ghost @click="handleTask('deleteb')">{{$L('删除')}}</Button>
            </div>
        </div>
    </div>
</template>

<script>
    import ProjectTaskLogs from "../logs";
    import ProjectTaskFiles from "../files";
    export default {
        components: {ProjectTaskFiles, ProjectTaskLogs},
        data() {
            return {
                taskid: 0,
                detail: {},

                visible: false,

                bakData: {},
                loadData: {},

                commentText: '',
                logType: '评论',

                timeValue: [],
                timeOptions: {},

                myUsername: '',
            }
        },
        beforeCreate() {
            let doms = document.querySelectorAll('.project-task-detail-window');
            for (let i = 0; i < doms.length; ++i) {
                if (doms[i].parentNode != null) doms[i].parentNode.removeChild(doms[i]);
            }
        },
        created() {
            this.timeOptions = {
                shortcuts: [{
                    text: this.$L('今天'),
                    value() {
                        return [new Date(), new Date()];
                    }
                }, {
                    text: this.$L('明天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 1);
                        return [new Date(), e];
                    }
                }, {
                    text: this.$L('本周'),
                    value() {
                        return [$A.getData('今天', true), $A.getData('本周结束', true)];
                    }
                }, {
                    text: this.$L('本月'),
                    value() {
                        return [$A.getData('今天', true), $A.getData('本月结束', true)];
                    }
                }, {
                    text: this.$L('3天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 3);
                        return [new Date(), e];
                    }
                }, {
                    text: this.$L('5天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 5);
                        return [new Date(), e];
                    }
                }, {
                    text: this.$L('7天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 7);
                        return [new Date(), e];
                    }
                }]
            };
        },
        mounted() {
            this.$nextTick(() => {
                let dom = this.$el;
                if (parseInt(this.taskid) === 0) {
                    if (dom.parentNode != null) dom.parentNode.removeChild(dom);
                    return;
                }
                //
                dom.addEventListener('transitionend', () => {
                    if (dom !== null && dom.parentNode !== null && !this.visible) {
                        dom.parentNode.removeChild(dom);
                    }
                }, false);
                //
                setTimeout(() => {
                    this.visible = true;
                }, 0)
            });
            this.bakData = $A.cloneData(this.detail);
            this.myUsername = $A.getUserName();
            this.getTaskDetail();
            //
            $A.setOnUserInfoListener("components/project/task/detail", () => {
                this.myUsername = $A.getUserName();
            });
            $A.setOnTaskInfoListener('components/project/task/detail',(act, detail) => {
                if (detail.id != this.taskid) {
                    return;
                }
                if (detail.__modifyUsername == this.myUsername) {
                    return;
                }
                this.getTaskDetail();
            }, true);
        },
        watch: {
            taskid() {
                this.bakData = $A.cloneData(this.detail);
                this.getTaskDetail();
            }
        },
        methods: {
            levelFormt(p) {
                switch (parseInt(p)) {
                    case 1:
                        return this.$L("重要且紧急") + " (P1)";
                    case 2:
                        return this.$L("重要不紧急") + " (P2)";
                    case 3:
                        return this.$L("紧急不重要") + " (P3)";
                    case 4:
                        return this.$L("不重要不紧急") + " (P4)";
                }
            },

            descKeydown(e) {
                e = e || event;
                if (e.keyCode == 13) {
                    if (e.shiftKey) {
                        return;
                    }
                    e.preventDefault();
                    e.target.blur();
                }
            },

            commentKeydown(e) {
                e = e || event;
                if (e.keyCode == 13) {
                    if (e.shiftKey) {
                        return;
                    }
                    e.preventDefault();
                    this.handleTask('comment');
                }
            },

            followerLength(follower) {
                if (follower instanceof Array) {
                    return follower.length;
                } else {
                    return 0;
                }
            },

            followerToStr(follower) {
                if (follower instanceof Array) {
                    return follower.join(",");
                } else {
                    return '';
                }
            },

            haveAttention(follower) {
                if (follower instanceof Array) {
                    return follower.filter((uname) => { return uname == this.myUsername }).length > 0
                } else {
                    return 0;
                }
            },

            getTaskDetail() {
                $A.aAjax({
                    url: 'project/task/lists',
                    data: {
                        taskid: this.taskid,
                        archived: '全部'
                    },
                    error: () => {
                        alert(this.$L('网络繁忙，请稍后再试！'));
                        this.visible = false;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.detail = res.data;
                            this.bakData = $A.cloneData(this.detail);
                        } else {
                            this.$Modal.error({
                                title: this.$L('温馨提示'),
                                content: res.msg,
                                onOk: () => {
                                    this.visible = false;
                                }
                            });
                        }
                    }
                });
            },

            handleTask(act, eve) {
                if (!!this.loadData[act]) {
                    this.$Message.info(this.$L('请稍候...'));
                    return;
                }
                //
                let ajaxData = {
                    act: act,
                    taskid: this.taskid,
                };
                let ajaxCallback = () => {};
                //
                switch (act) {
                    case 'title':
                    case 'desc':
                        if (this.detail[act] == this.bakData[act]) {
                            return;
                        }
                        if (!this.detail[act]) {
                            this.$set(this.detail, act, this.bakData[act]);
                            return;
                        }
                        ajaxData.content = this.detail[act];
                        ajaxCallback = (res) => {
                            if (res !== 1) {
                                this.$set(this.detail, act, this.bakData[act]);
                            }
                        };
                        break;

                    case 'fileupload':
                        this.$refs.upload.uploadHandleClick();
                        return;

                    case 'filechange':
                        let filenum = $A.runNum(this.detail.filenum);
                        switch (eve) {
                            case 'up':
                                this.$set(this.detail, 'filenum', filenum + 1);
                                break;
                            case 'error':
                            case 'delete':
                                this.$set(this.detail, 'filenum', filenum - 1);
                                break;
                        }
                        if (eve == 'add' || eve == 'delete') {
                            this.logType == '日志' && this.$refs.log.getLists(true, true);
                        }
                        return;

                    case 'complete':
                    case 'unfinished':
                    case 'archived':
                    case 'unarchived':
                        break;

                    case 'archived2':
                        ajaxData.act = 'complete';
                        ajaxCallback = (res) => {
                            if (res === 1 && !this.detail.archived) {
                                this.handleTask('archived');
                                return false;
                            }
                        };
                        break;

                    case 'level-1':
                    case 'level-2':
                    case 'level-3':
                    case 'level-4':
                        ajaxData.act = 'level';
                        ajaxData.content = act.substring(6);
                        break;

                    case 'usernameb':
                        if (!eve.username) {
                            return;
                        }
                        this.$Modal.confirm({
                            title: this.$L('修改负责人'),
                            content: this.$L('你确定修改负责人设置为“%”吗？', (eve.nickname || eve.username)),
                            onOk: () => {
                                this.handleTask('username', eve);
                            }
                        });
                        return;

                    case 'username':
                        if (!eve.username) {
                            return;
                        }
                        ajaxData.content = eve.username;
                        ajaxCallback = (res) => {
                            if (res === 1) {
                                this.$Modal.info({
                                    title: this.$L('温馨提示'),
                                    content: this.$L('任务负责人已改变，点击确定关闭窗口。'),
                                    onOk: () => {
                                        this.visible = false;
                                    }
                                });
                            }
                        };
                        break;

                    case 'inittime':
                        if (this.detail.startdate > 0 && this.detail.enddate > 0) {
                            this.timeValue = [$A.formatDate("Y-m-d H:i", this.detail.startdate), $A.formatDate("Y-m-d H:i", this.detail.enddate)]
                        } else {
                            this.timeValue = [];
                        }
                        return;

                    case 'plannedtimeb':
                        let temp = $A.date2string(this.timeValue, "Y-m-d H:i");
                        this.$Modal.confirm({
                            title: this.$L('修改计划时间'),
                            content: this.$L('你确定将任务计划时间设置为“%”吗？', temp[0] + "~" + temp[1]),
                            onOk: () => {
                                this.handleTask('plannedtime');
                            }
                        });
                        return;

                    case 'plannedtime':
                        this.timeValue = $A.date2string(this.timeValue);
                        ajaxData.content = this.timeValue[0] + "," + this.timeValue[1];
                        this.$refs.timeRef.handleClose();
                        break;

                    case 'unplannedtimeb':
                        this.$Modal.confirm({
                            title: this.$L('取消计划时间'),
                            content: this.$L('你确定将任务计划时间取消吗？'),
                            onOk: () => {
                                this.handleTask('unplannedtime');
                            }
                        });
                        return;

                    case 'unplannedtime':
                        this.$refs.timeRef.handleClose();
                        break;

                    case 'attentiona':
                        ajaxData.act = "attention";
                        ajaxData.content = this.myUsername;
                        break;

                    case 'attention':
                        if (!this.detail.attentionLists) {
                            return;
                        }
                        ajaxData.content = this.detail.attentionLists;
                        this.$refs.attentionRef.handleClose();
                        break;

                    case 'unattention':
                        ajaxData.content = eve.username;
                        if (eve.uisynch === true) {
                            let bakFollower = $A.cloneData(this.detail.follower);
                            this.$set(this.detail, 'follower', this.detail.follower.filter((uname) => { return uname != eve }));
                            ajaxCallback = (res) => {
                                if (res !== 1) {
                                    this.$set(this.detail, 'follower', bakFollower);
                                }
                            };
                        }
                        break;

                    case 'deleteb':
                        this.$Modal.confirm({
                            title: this.$L('删除提示'),
                            content: this.$L('您确定要删除此任务吗？'),
                            onOk: () => {
                                this.handleTask('delete');
                            },
                        });
                        return;

                    case 'delete':
                        ajaxCallback = (res) => {
                            if (res === 1) {
                                this.$Modal.info({
                                    title: this.$L('温馨提示'),
                                    content: this.$L('任务已删除，点击确定关闭窗口。'),
                                    onOk: () => {
                                        this.visible = false;
                                    }
                                });
                            }
                        };
                        break;

                    case 'comment':
                        if (!this.commentText) {
                            return;
                        }
                        ajaxData.content = this.commentText;
                        ajaxCallback = (res) => {
                            if (res === 1) {
                                this.commentText = "";
                                this.logType == '评论' && this.$refs.log.getLists(true, true);
                            }
                        };
                        break;

                    default: {
                        return;
                    }
                }
                //
                this.$set(this.loadData, ajaxData.act, true);
                $A.aAjax({
                    url: 'project/task/edit',
                    data: ajaxData,
                    complete: () => {
                        this.$set(this.loadData, ajaxData.act, false);
                    },
                    error: () => {
                        ajaxCallback(-1);
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.detail = res.data;
                            this.bakData = $A.cloneData(this.detail);
                            if (ajaxCallback(1) !== false) {
                                this.logType == '日志' && this.$refs.log.getLists(true, true);
                                this.$Message.success(res.msg);
                            }
                            $A.triggerTaskInfoListener(ajaxData.act, res.data);
                        } else {
                            ajaxCallback(0);
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                        }
                    }
                });
            }
        }
    }
</script>

<style lang="scss" scoped>
    .project-task-detail-window {
        position: fixed;
        z-index: 1001;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        transition: all .3s;
        opacity: 0;
        pointer-events: unset;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;

        &.task-detail-show {
            opacity: 1;
        }

        .task-detail-main {
            display: flex;
            flex-direction: row;
            width: 92%;
            max-width: 800px;
            max-height: 92%;
            background: #ffffff;
            overflow: visible;
            border-radius: 4px;
            padding: 10px 20px 2px;
            transform: translateZ(0);
            .detail-left {
                flex: 1;
                padding-left: 8px;
                padding-right: 20px;
                overflow: auto;
                .detail-h2 {
                    color: #172b4d;
                    font-size: 16px;
                    display: flex;
                    align-items: center;
                    line-height: 26px;
                    strong {
                        font-size: 14px;
                        font-weight: normal;
                        &.link {
                            cursor: pointer;
                        }
                        &.active {
                            font-size: 16px;
                            font-weight: bold;
                        }
                    }
                    em {
                        margin: 0 9px;
                        width: 1px;
                        height: 10px;
                        background: #cccccc;
                    }
                }
                .detail-icon {
                    position: relative;
                    padding-left: 26px;
                    &:before {
                        font-family: zenicon;
                        font-size: 20px;
                        color: #42526e;
                        font-weight: 600;
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 26px;
                        height: 26px;
                        line-height: 26px;
                    }
                }
                .detail-title-box {
                    margin-top: 12px;
                    margin-bottom: 12px;
                    &:before {
                        content: "\E740";
                    }
                    .time {
                        font-size: 12px;
                        color: #606266;
                    }
                    input {
                        margin: -10px 0 0 -8px;
                        font-size: 20px;
                        font-weight: 600;
                        border: 2px solid #ffffff;
                        padding: 5px 8px;
                        cursor: pointer;
                        color: #172b4d;
                        background: #ffffff;
                        width: 100%;
                        border-radius: 3px;
                    }
                    input:focus {
                        outline: 0;
                        background: #fff;
                        border-color: #0396f2;
                    }
                }
                .detail-desc-box {
                    &:before {
                        content: "\E75E";
                    }
                    textarea {
                        border: 2px solid #F4F5F7;
                        padding: 5px 8px;
                        cursor: pointer;
                        color: #172b4d;
                        background: rgba(9, 30, 66, 0.04);
                        width: 100%;
                        border-radius: 3px;
                        resize: none;
                        margin-top: 10px;
                        &:focus {
                            outline: 0;
                            background: #fff;
                            border-color: #0396f2;
                        }
                    }
                }
                .detail-text-box {
                    margin-bottom: 12px;
                    li {
                        color: #606266;
                        font-size: 14px;
                        line-height: 32px;
                        word-break: break-all;
                        display: flex;
                        &:before {
                            font-weight: normal;
                            color: #606266;
                            font-size: 14px;
                            padding-left: 4px;
                            line-height: 32px;
                        }
                        &.text-time {
                            &:before {
                                content: "\E706";
                            }
                        }
                        &.text-username {
                            &:before {
                                content: "\E903";
                            }
                        }
                        &.text-follower {
                            &:before {
                                content: "\E90D";
                            }
                        }
                        &.text-level {
                            &:before {
                                content: "\E725";
                            }
                        }
                        &.text-status {
                            &:before {
                                content: "\E6AF";
                            }
                        }
                        > span {
                            white-space: nowrap;
                        }
                        > em {
                            margin-left: 4px;
                            padding-top: 5px;
                            line-height: 22px;
                            &.p1 {
                                color: #ed3f14;
                            }
                            &.p2 {
                                color: #ff9900;
                            }
                            &.p3 {
                                color: #19be6b;
                            }
                            &.p4 {
                                color: #666666;
                            }
                            &.complete {
                                color: #666666;
                            }
                            &.overdue {
                                color: #ff0000;
                            }
                            &.unfinished {
                                color: #19be6b;
                            }
                        }
                    }
                }
                .detail-file-box {
                    &:before {
                        content: "\E8B9";
                        font-size: 16px;
                        padding-left: 2px;
                    }
                }
                .detail-comment-box {
                    &:before {
                        content: "\E753";
                    }
                }
                .detail-footer-box {
                    border-top: 1px solid #e5e5e5;
                    display: flex;
                    flex-direction: row;
                    padding-top: 20px;
                    padding-bottom: 16px;
                    .comment-input {
                        margin-right: 12px;
                    }
                }
            }
            .detail-right {
                .cancel {
                    text-align: right;
                    width: auto;
                    height: 38px;
                    em {
                        display: inline-block;
                        width: 38px;
                        height: 38px;
                        cursor: pointer;
                        border-radius: 50%;
                        transform: scale(0.92);
                        &:after,
                        &:before {
                            position: absolute;
                            content: "";
                            top: 50%;
                            left: 50%;
                            width: 2px;
                            height: 20px;
                            background-color: #EE2321;
                            transform: translate(-50%, -50%) rotate(45deg) scale(0.6, 1);
                            transition: all .2s;
                        }
                        &:before {
                            position: absolute;
                            transform: translate(-50%, -50%) rotate(-45deg) scale(0.6, 1);
                        }
                        &:hover {
                            &:after,
                            &:before {
                                background-color: #ff0000;
                                transform: translate(-50%, -50%) rotate(135deg) scale(0.6, 1);
                            }
                            &:before {
                                background-color: #ff0000;
                                transform: translate(-50%, -50%) rotate(45deg) scale(0.6, 1);
                            }
                        }
                    }
                }
                .block {
                    display: block;
                    .p1 {
                        color: #ed3f14;
                    }
                    .p2 {
                        color: #ff9900;
                    }
                    .p3 {
                        color: #19be6b;
                    }
                    .p4 {
                        color: #666666;
                    }
                    .checkmark {
                        margin-left: 8px;
                        margin-right: -8px;
                    }
                }
                .btn {
                    display: block;
                    width: 118px;
                    text-align: left;
                    margin-top: 8px;
                    padding-left: 10px;
                    padding-right: 10px;
                }
            }
        }
    }
</style>
