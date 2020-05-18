<template>
    <div class="project-task-detail-window" :class="{'task-detail-show': visible}">
        <div class="task-detail-main">
            <div class="detail-left">
                <div class="detail-title-box detail-icon">
                    <input v-model="detail.title" :disabled="!!loadData.title" type="text" maxlength="60" @blur="handleTask('title')">
                    <div class="time">
                        <span class="z-nick">{{detail.createuser}}</span>
                        创建于：
                        <span>{{$A.formatDate("Y-m-d H:i:s", detail.indate)}}</span>
                    </div>
                </div>
                <div class="detail-desc-box detail-icon">
                    <div class="detail-h2"><strong>描述</strong></div>
                    <textarea v-model="detail.desc" placeholder="添加详细描述..." @blur="handleTask('desc')"></textarea>
                </div>
                <ul class="detail-text-box">
                    <li v-if="detail.startdate > 0 && detail.enddate > 0" class="text-time detail-icon">
                        计划时间：
                        <em>{{$A.formatDate("Y-m-d H:i", detail.startdate)}} 至 {{$A.formatDate("Y-m-d H:i", detail.enddate)}}</em>
                    </li>
                    <li class="text-username detail-icon">
                        负责人：
                        <em>{{detail.username}}</em>
                    </li>
                    <li class="text-level detail-icon">
                        优先级：
                        <em :class="`p${detail.level}`">{{levelFormt(detail.level)}}</em>
                    </li>
                    <li class="text-status detail-icon">
                        任务状态：
                        <em v-if="detail.overdue" class="overdue">已超期</em>
                        <em v-else-if="detail.complete" class="complete">已完成</em>
                        <em v-else class="unfinished">未完成</em>
                    </li>
                </ul>
                <div :style="`${detail.filenum>0?'':'display:none'}`">
                    <div class="detail-h2 detail-file-box detail-icon"><strong>附件</strong></div>
                    <project-task-files ref="upload" :taskid="taskid" :simple="true" @change="handleTask('filechange', $event)"></project-task-files>
                </div>
                <div class="detail-h2 detail-comment-box detail-icon"><strong>评论</strong><em></em><strong>操作记录</strong></div>
                <div class="detail-log-box">
                    <project-task-logs ref="log" :projectid="detail.projectid" :taskid="taskid" :pagesize="5"></project-task-logs>
                </div>
                <div class="detail-footer-box">
                    <Input class="comment-input" v-model="commentText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" @on-keydown="commentKeydown" placeholder="输入评论，Enter发表评论，Shift+Enter换行" />
                    <Button type="primary">评 论</Button>
                </div>
            </div>
            <div class="detail-right">
                <div class="cancel"><em @click="visible=false"></em></div>
                <div class="btn">
                    <Dropdown trigger="click" @on-click="handleTask">
                        <i class="ft icon">&#xE6CB;</i>标记{{detail.complete?'未完成':'已完成'}}
                        <DropdownMenu slot="list">
                            <DropdownItem name="complete">标记已完成<Icon v-if="detail.complete && !detail.archived" type="md-checkmark" class="checkmark"/></DropdownItem>
                            <DropdownItem name="unfinished">标记未完成<Icon v-if="!detail.complete" type="md-checkmark" class="checkmark"/></DropdownItem>
                            <DropdownItem name="archived2">完成并归档<Icon v-if="detail.complete && detail.archived" type="md-checkmark" class="checkmark"/></DropdownItem>
                        </DropdownMenu>
                    </Dropdown>
                </div>
                <div class="btn">
                    <Dropdown trigger="click" @on-click="handleTask">
                        <i class="ft icon">&#xE7AD;</i>优先级
                        <DropdownMenu slot="list">
                            <DropdownItem v-for="level in [1,2,3,4]" :key="level" :name="`level-${level}`" :class="`p${level}`">{{levelFormt(level)}}<Icon v-if="detail.level==level" type="md-checkmark" class="checkmark"/></DropdownItem>
                        </DropdownMenu>
                    </Dropdown>
                </div>
                <div class="btn">
                    <Poptip placement="bottom" transfer>
                        <i class="ft icon">&#xE6FD;</i>负责人
                        <div slot="content">
                            <div style="width:240px">
                                选择负责人
                                <UseridInput :projectid="detail.projectid" @change="handleTask('username', $event)" placeholder="输入关键词搜索" style="margin:5px 0 3px"></UseridInput>
                            </div>
                        </div>
                    </Poptip>
                </div>
                <div class="btn">
                    <Poptip ref="timeRef" placement="bottom" @on-popper-show="handleTask('opentime')" transfer>
                        <i class="ft icon">&#xE706;</i>计划时间
                        <div slot="content">
                            <div style="width:280px">
                                选择日期范围
                                <Date-picker
                                    v-model="timeValue"
                                    :options="timeOptions"
                                    :placeholder="$L('日期范围')"
                                    format="yyyy-MM-dd HH:mm"
                                    type="datetimerange"
                                    placement="bottom"
                                    @on-ok="handleTask('plannedtime')"
                                    @on-clear="handleTask('unplannedtime')"
                                    style="display:block;margin:5px 0 3px"></Date-picker>
                            </div>
                        </div>
                    </Poptip>
                </div>
                <div class="btn" @click="handleTask('fileupload')"><i class="ft icon">&#xE701;</i>添加附件</div>
                <div v-if="!detail.archived" class="btn" @click="handleTask('archived')"><i class="ft icon">&#xE85F;</i>归档</div>
                <div v-else class="btn" @click="handleTask('unarchived')"><i class="ft icon">&#xE85F;</i>取消归档</div>
                <div class="btn remove" @click="handleTask('deleteb')"><i class="ft icon">&#xE6FB;</i>删除</div>
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
                visible: false,
                taskid: 0,
                detail: {},
                bakData: {},
                loadData: {},
                commentText: '',

                timeValue: [],
                timeOptions: {
                    shortcuts: [{
                        text: '今天',
                        value() {
                            return [new Date(), new Date()];
                        }
                    }, {
                        text: '明天',
                        value() {
                            let e = new Date();
                            e.setDate(e.getDate() + 1);
                            return [new Date(), e];
                        }
                    }, {
                        text: '本周',
                        value() {
                            return [$A.getData('今天'), $A.getData('本周结束')];
                        }
                    }, {
                        text: '本月',
                        value() {
                            return [$A.getData('今天'), $A.getData('本月结束')];
                        }
                    }, {
                        text: '3天',
                        value() {
                            let e = new Date();
                            e.setDate(e.getDate() + 3);
                            return [new Date(), e];
                        }
                    }, {
                        text: '5天',
                        value() {
                            let e = new Date();
                            e.setDate(e.getDate() + 5);
                            return [new Date(), e];
                        }
                    }, {
                        text: '7天',
                        value() {
                            let e = new Date();
                            e.setDate(e.getDate() + 7);
                            return [new Date(), e];
                        }
                    }]
                },
            }
        },
        beforeCreate() {
            let doms = document.querySelectorAll('.project-task-detail-window');
            for (let i = 0; i < doms.length; ++i) {
                if (doms[i].parentNode != null) doms[i].parentNode.removeChild(doms[i]);
            }
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
        },
        watch: {
            taskid() {
                this.bakData = $A.cloneData(this.detail);
            }
        },
        methods: {
            levelFormt(p) {
                switch (parseInt(p)) {
                    case 1:
                        return "重要且紧急 (P1)";
                    case 2:
                        return "重要不紧急 (P2)";
                    case 3:
                        return "紧急不重要 (P3)";
                    case 4:
                        return "不重要不紧急 (P4)";
                }
            },

            commentKeydown(e) {
                e = e || event;
                if (e.keyCode == 13) {
                    if (e.shiftKey) {
                        return;
                    }
                    e.preventDefault();
                }
            },

            handleTask(act, eve) {
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
                            this.$refs.log.getLists(true, true);
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

                    case 'username':
                        if (!eve.username) {
                            return;
                        }
                        ajaxData.content = eve.username;
                        ajaxCallback = (res) => {
                            if (res === 1) {
                                this.visible = false;
                            }
                        };
                        break;

                    case 'opentime':
                        if (this.detail.startdate > 0 && this.detail.enddate > 0) {
                            this.timeValue = [$A.formatDate("Y-m-d H:i", this.detail.startdate), $A.formatDate("Y-m-d H:i", this.detail.enddate)]
                        } else {
                            this.timeValue = [];
                        }
                        return;

                    case 'plannedtime':
                        this.timeValue = $A.date2string(this.timeValue);
                        ajaxData.content = this.timeValue[0] + "," + this.timeValue[1];
                        this.$refs.timeRef.handleClose();
                        break;

                    case 'unplannedtime':
                        this.$refs.timeRef.handleClose();
                        return;

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
                                this.visible = false;
                            }
                        };
                        break;
                }
                //
                this.$set(this.loadData, act, true);
                $A.aAjax({
                    url: 'project/task/edit',
                    data: ajaxData,
                    complete: () => {
                        this.$set(this.loadData, act, false);
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
                                this.$Message.success(res.msg);
                                this.$refs.log.getLists(true, true);
                            }
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
        z-index: 99;
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
            width: 96%;
            max-width: 800px;
            max-height: 96%;
            background: #ffffff;
            overflow: hidden;
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
                    em {
                        margin: 0 11px;
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
                        line-height: 30px;
                        word-break: break-all;
                        &:before {
                            font-weight: normal;
                            color: #606266;
                            font-size: 14px;
                            padding-left: 4px;
                            line-height: 30px;
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
                        em {
                            margin-left: 4px;
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
                .btn {
                    background: rgba(9, 30, 66, 0.04);
                    border: 1px solid #E8EAEE;
                    border-radius: 4px;
                    padding: 4px 8px;
                    margin-top: 6px;
                    color: #172b4d;
                    cursor: pointer;
                    display: block;
                    &:hover {
                        border-color: #409EFF;
                        background: #409EFF;
                        color: #fff;
                    }
                    &.remove {
                        color: rgba(248, 14, 21, 0.5);
                        &:hover {
                            border-color: #ffa5a3;
                            background: #ffa5a3;
                            color: #ffffff;
                        }
                    }
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
                    .icon {
                        margin-right: 4px;
                    }
                    .checkmark {
                        margin-left: 8px;
                        margin-right: -8px;
                    }
                }
            }
        }
    }
</style>
