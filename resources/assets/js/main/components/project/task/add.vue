<template>
    <div class="task-input-box">
        <div v-if="!addText" class="input-placeholder">
            <Icon type="md-create" size="18"/>&nbsp;{{placeholder}}
        </div>
        <div class="input-enter">
            <Input
                v-model="addText"
                type="textarea"
                class="input-enter-textarea"
                :class="{bright:addFocus===true,highlight:!!addText}"
                element-id="project-panel-enter-textarea"
                @on-focus="addFocus=true"
                @on-blur="addFocus=false"
                :autosize="{ minRows: 1, maxRows: 6 }"
                :maxlength="255"/>
            <div v-if="!!addText" class="input-enter-module">
                <Tooltip content="重要且紧急" placement="bottom" transfer><div @click="addLevel=1" class="enter-module-icon p1"><Icon v-if="addLevel=='1'" type="md-checkmark" /></div></Tooltip>
                <Tooltip content="重要不紧急" placement="bottom" transfer><div @click="addLevel=2" class="enter-module-icon p2"><Icon v-if="addLevel=='2'" type="md-checkmark" /></div></Tooltip>
                <Tooltip content="紧急不重要" placement="bottom" transfer><div @click="addLevel=3" class="enter-module-icon p3"><Icon v-if="addLevel=='3'" type="md-checkmark" /></div></Tooltip>
                <Tooltip content="不重要不紧急" placement="bottom" transfer><div @click="addLevel=4" class="enter-module-icon p4"><Icon v-if="addLevel=='4'" type="md-checkmark" /></div></Tooltip>
                <div class="enter-module-flex"></div>
                <Poptip placement="bottom" @on-popper-show="nameTipDisabled=true" @on-popper-hide="nameTipDisabled=false" transfer>
                    <Tooltip :content="`负责人: ${addUsername||'自己'}`" placement="bottom" :disabled="nameTipDisabled">
                        <div class="enter-module-icon user">
                            <img v-if="addUserimg" @error="addUserimg=''" :src="addUserimg"/>
                            <Icon v-else type="md-person" />
                        </div>
                    </Tooltip>
                    <div slot="content">
                        <div style="width:240px">
                            选择负责人
                            <UseridInput v-model="addUsername" :projectid="projectid" @change="changeUser" placeholder="留空默认: 自己" style="margin:5px 0 3px"></UseridInput>
                        </div>
                    </div>
                </Poptip>
                <Button class="enter-module-btn" type="info" size="small" @click="clickAdd">添加任务</Button>
            </div>
        </div>
        <div v-if="loadIng > 0" class="load-box" @click.stop="">
            <div class="load-box-main"><w-loading></w-loading></div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .task-input-box {
        position: relative;
        margin-top: 5px;
        .input-placeholder,
        .input-enter {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        .input-placeholder {
            z-index: 1;
            height: 40px;
            line-height: 40px;
            color: rgba(0, 0, 0, .36);
            padding-left: 12px;
            padding-right: 12px;
        }
        .input-enter {
            z-index: 2;
            position: relative;
            background-color: transparent;
            .input-enter-textarea {
                border-radius: 4px;
                padding-left: 12px;
                padding-right: 12px;
                color: rgba(0, 0, 0, 0.85);
                &.bright {
                    background-color: rgba(46, 73, 136, .08);
                }
                &.highlight {
                    background-color: #ffffff;
                }
            }
            .input-enter-module {
                display: flex;
                width: 100%;
                margin-top: 8px;
                .enter-module-icon {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    margin-right: 5px;
                    border-radius: 4px;
                    vertical-align: middle;
                    cursor: pointer;
                    &.p1 {
                        background-color: #ff0000;
                    }
                    &.p2 {
                        background-color: #BB9F35;
                    }
                    &.p3 {
                        background-color: #449EDD;
                    }
                    &.p4 {
                        background-color: #84A83B;
                    }
                    &.user {
                        background-color: #98CD75;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        margin-left: 10px;
                        margin-right: 10px;
                        img {
                            width: 100%;
                            height: 100%;
                            border-radius: 50%;
                        }
                        i {
                            line-height: 24px;
                            font-size: 16px;
                        }
                    }
                    i {
                        width: 100%;
                        height: 100%;
                        color: #ffffff;
                        line-height: 16px;
                        font-size: 14px;
                        transform: scale(0.85);
                        vertical-align: 0;
                    }
                }
                .enter-module-flex {
                    flex: 1;
                }
                .enter-module-btn {
                    font-size: 12px;
                }
            }
        }
        .load-box {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9;
            .load-box-main {
                width: 24px;
                height: 24px;
            }
        }
    }
</style>
<script>
    import WLoading from "../../WLoading";
    export default {
        name: 'ProjectAddTask',
        components: {WLoading},
        props: {
            placeholder: {
                type: String,
                default: ''
            },
            projectid: {
                type: Number,
                default: 0
            },
            labelid: {
                type: Number,
                default: 0
            },
        },
        data() {
            return {
                loadIng: 0,

                addText: '',
                addLevel: 2,
                addUsername: '',

                addUserimg: '',
                addFocus: false,

                nameTipDisabled: false,
            }
        },
        methods: {
            changeUser(user) {
                this.addUserimg = user.userimg;
            },
            clickAdd() {
                this.loadIng++;
                $A.aAjax({
                    url: 'project/task/add',
                    data: {
                        projectid: this.projectid,
                        labelid: this.labelid,
                        title: this.addText,
                        level: this.addLevel,
                        username: this.addUsername,
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.addText = '';
                            this.addFocus = false;
                            this.$Message.success(res.msg);
                            this.$emit('on-add-success', res.data);
                        } else {
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                        }
                    }
                });
            }
        }
    }
</script>
