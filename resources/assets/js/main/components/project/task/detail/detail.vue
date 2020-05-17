<template>
    <div class="project-task-detail-window" :class="{'task-detail-show': visible}">
        <div class="task-detail-main">
            <div class="detail-left">
                <div class="detail-title-box detail-icon">
                    <input v-model="detail.title" type="text" maxlength="60">
                    <div class="time">
                        <span class="z-nick">{{detail.createuser}}</span>
                        创建于：
                        <span>{{$A.formatDate("Y-m-d H:i:s", detail.indate)}}</span>
                    </div>
                </div>
                <div class="detail-desc-box detail-icon">
                    <div class="detail-h2"><strong>描述</strong></div>
                    <textarea placeholder="添加详细描述..."></textarea>
                </div>
                <ul class="detail-text-box">
                    <li class="text-username detail-icon">
                        负责人：
                        <em>{{detail.username}}</em>
                    </li>
                    <li class="text-level detail-icon">
                        优先级：
                        <em :class="`p${detail.level}`">P{{detail.level}}</em>
                    </li>
                    <li class="text-status detail-icon">
                        任务状态：
                        <em v-if="detail.overdue" class="overdue">已超期</em>
                        <em v-else-if="detail.complete" class="complete">已完成</em>
                        <em v-else class="unfinished">未完成</em>
                    </li>
                </ul>
                <div class="detail-h2 detail-comment-box detail-icon"><strong>评论</strong><em></em><strong>操作记录</strong></div>
                <div class="detail-log-box">
                    <project-task-logs :projectid="detail.projectid" :taskid="detail.taskid" :pagesize="5"></project-task-logs>
                </div>
                <div class="detail-footer-box">
                    <Input class="comment-input" v-model="commentText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" @on-keydown="commentKeydown" placeholder="输入评论，Enter发表评论，Shift+Enter换行" />
                    <Button type="primary">评 论</Button>
                </div>
            </div>
            <div class="detail-right">
                <div class="cancel"><em @click="visible=false"></em></div>
                <div class="btn"><i class="ft icon">&#xE6CB;</i>标记已完成</div>
                <div class="btn"><i class="ft icon">&#xE7AD;</i>优先级</div>
                <div class="btn"><i class="ft icon">&#xE6FD;</i>负责人</div>
                <div class="btn"><i class="ft icon">&#xE706;</i>计划时间</div>
                <div class="btn"><i class="ft icon">&#xE701;</i>附件</div>
                <div class="btn"><i class="ft icon">&#xE85F;</i>归档</div>
                <div class="btn remove"><i class="ft icon">&#xE6FB;</i>删除</div>
            </div>
        </div>
    </div>
</template>

<script>
    import ProjectTaskLogs from "../logs";
    export default {
        components: {ProjectTaskLogs},
        data() {
            return {
                visible: false,
                taskid: 0,
                detail: {},
                commentText: '',
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
        },
        methods: {
            commentKeydown(e) {
                e = e || event;
                if (e.keyCode == 13) {
                    if (e.shiftKey) {
                        console.log("换行");
                        return;
                    }
                    console.log("发表");
                    e.preventDefault();
                }
            }
        }
    }
</script>

<style lang="scss" scoped>
    .project-task-detail-window {
        position: fixed;
        z-index: 100000;
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
                    }
                    textarea:focus {
                        outline: 0;
                        background: #fff;
                        height: 100px;
                        border-color: #0396f2;
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
                    .icon {
                        margin-right: 4px;
                    }
                }
            }
        }
    }
</style>
