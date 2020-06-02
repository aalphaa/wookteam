<template>
    <div class="w-header">
        <div v-if="tabActive" class="w-header-row">
            <div class="w-header-row-left">
                <ul>
                    <li :class="tabActive==='todo'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('todo')"><i class="ft icon">&#xe89e;</i>{{$L('待办')}}</a>
                    </li><li :class="tabActive==='project'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('project')"><i class="ft icon">&#xe6b8;</i>{{$L('项目')}}</a>
                    </li><li :class="tabActive==='docs'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('docs')"><i class="ft icon">&#xe915;</i>{{$L('知识库')}}</a>
                    </li><li :class="tabActive==='team'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('team')"><i class="ft icon">&#xe90d;</i>{{$L('团队')}}</a>
                    </li>
                </ul>
            </div>
            <div class="w-header-row-right">
                <Dropdown class="right-info" trigger="click" @on-click="setRightSelect" placement="bottom-end" transfer>
                   <div>
                       <span class="username">{{$L('欢迎您')}}, {{(userInfo.nickname || userInfo.username) || $L('尊敬的会员')}}</span>
                       <Icon type="md-arrow-dropdown"/>
                   </div>
                    <Dropdown-menu slot="list">
                        <Dropdown-item name="user">{{$L('个人中心')}}</Dropdown-item>
                        <Dropdown-item name="out">{{$L('退出登录')}}</Dropdown-item>
                    </Dropdown-menu>
                </Dropdown>
                <div class="right-info" @click="chatDrawerShow=true">
                    <Icon class="right-mticon" type="md-notifications" size="24"/>
                </div>
                <Dropdown class="right-info" trigger="click" @on-click="setLanguage" transfer>
                    <div>
                        <Icon class="right-mticon" type="md-globe" size="24"/>
                        <Icon type="md-arrow-dropdown"/>
                    </div>
                    <Dropdown-menu slot="list">
                        <Dropdown-item name="zh" :selected="getLanguage() === 'zh'">中文</Dropdown-item>
                        <Dropdown-item name="en" :selected="getLanguage() === 'en'">English</Dropdown-item>
                    </Dropdown-menu>
                </Dropdown>
            </div>
        </div>
        <WDrawer v-model="userDrawerShow" maxWidth="1000">
            <Tabs v-model="userDrawerTab">
                <TabPane :label="$L('个人资料')" name="personal">
                    <Form ref="formDatum" :model="formDatum" :rules="ruleDatum" :label-width="80">
                        <FormItem :label="$L('头像')" prop="userimg">
                            <ImgUpload v-model="formDatum.userimg"></ImgUpload>
                        </FormItem>
                        <FormItem :label="$L('账号')">
                            <Input v-model="userInfo.username" :disabled="true"></Input>
                        </FormItem>
                        <FormItem :label="$L('昵称')" prop="nickname">
                            <Input v-model="formDatum.nickname"></Input>
                        </FormItem>
                        <FormItem :label="$L('职位/职称')" prop="profession">
                            <Input v-model="formDatum.profession"></Input>
                        </FormItem>
                        <FormItem>
                            <Button :loading="loadIng > 0" type="primary" @click="handleSubmit('formDatum')">{{$L('提交')}}</Button>
                            <Button :loading="loadIng > 0" @click="handleReset('formDatum')" style="margin-left: 8px">{{$L('重置')}}</Button>
                        </FormItem>
                    </Form>
                </TabPane>
                <TabPane :label="$L('偏好设置')" name="setting">
                    <Form ref="formSetting" :model="formSetting" :label-width="100">
                        <FormItem :label="$L('系统皮肤')" prop="bgid">
                            <ul class="setting-bg">
                                <li v-for="i in [1,2,3,4,5,6,7,8,9,10,11,12]"
                                    :key="i"
                                    :style="`background-image:${getBgUrl(i, true)}`"
                                    :class="{active:formSetting.bgid==i}"
                                    @click="formSetting.bgid=i"></li>
                            </ul>
                        </FormItem>
                        <FormItem>
                            <Button :loading="loadIng > 0" type="primary" @click="handleSubmit('formSetting')">{{$L('提交')}}</Button>
                            <Button :loading="loadIng > 0" @click="handleReset('formSetting')" style="margin-left: 8px">{{$L('重置')}}</Button>
                        </FormItem>
                    </Form>
                </TabPane>
                <TabPane :label="$L('账号密码')" name="account">
                    <Form ref="formPass" :model="formPass" :rules="rulePass" :label-width="100">
                        <FormItem :label="$L('旧密码')" prop="oldpass">
                            <Input v-model="formPass.oldpass"></Input>
                        </FormItem>
                        <FormItem :label="$L('新密码')" prop="newpass">
                            <Input v-model="formPass.newpass"></Input>
                        </FormItem>
                        <FormItem :label="$L('确认新密码')" prop="checkpass">
                            <Input v-model="formPass.checkpass"></Input>
                        </FormItem>
                        <FormItem>
                            <Button :loading="loadIng > 0" type="primary" @click="handleSubmit('formPass')">{{$L('提交')}}</Button>
                            <Button :loading="loadIng > 0" @click="handleReset('formPass')" style="margin-left: 8px">{{$L('重置')}}</Button>
                        </FormItem>
                    </Form>
                </TabPane>
                <TabPane :label="$L('我创建的任务')" name="createtask">
                    <header-create :canload="userDrawerShow && userDrawerTab == 'createtask'"></header-create>
                </TabPane>
                <TabPane :label="$L('我归档的任务')" name="archivedtask">
                    <header-archived :canload="userDrawerShow && userDrawerTab == 'archivedtask'"></header-archived>
                </TabPane>
            </Tabs>
        </WDrawer>
        <WDrawer v-model="chatDrawerShow" :closable="false" maxWidth="1080">
            <chat-index></chat-index>
        </WDrawer>
    </div>
</template>

<style lang="scss" scoped>
    .w-header {
        z-index: 15;
        position: fixed;
        left: 0;
        top: 0;
        right: 0;
        font-size: 14px;
        background: #0396f2 linear-gradient(45deg, #0396f2 0%, #0285d7 100%);
        box-shadow: 0 1px 5px 0 rgba(0, 0, 0, 0.25);

        .icon {
            font-size: 16px;
            margin-right: 3px;
        }

        .w-header-row {
            display: flex;
            color: #fff;
            height: 40px;
            position: relative;
            z-index: 10;
            margin: 0 32px;
            .w-header-row-left {
                flex: 1;
                white-space: nowrap;
                overflow: hidden;
                overflow-x: auto;
                -webkit-backface-visibility: hidden;
                -webkit-overflow-scrolling: touch;
                li {
                    line-height: 40px;
                    color: #fff;
                    display: inline-block;
                    a {
                        color: #fff;
                        display: block;
                        width: 116px;
                        text-align: center;
                        &:visited {
                            color: #fff;
                        }
                        &:hover {
                            color: #f2f2f2;
                        }
                    }
                }
                li:hover, li.active {
                    background: #0277c0;
                }
            }
            .w-header-row-right {
                white-space: nowrap;
                text-align: right;
                line-height: 40px;
                .right-info {
                    display: inline-block;
                    position: relative;
                    margin-left: 12px;
                    cursor: pointer;
                    .right-mticon {
                        vertical-align: top;
                        margin-top: 8px;
                    }
                }
            }
        }
    }
    .setting-bg {
        margin-top: 6px;
        margin-bottom: -24px;
        &:after,
        &:before {
            display: table;
            content: "";
        }
        li {
            margin: 0 16px 16px 0;
            width: 160px;
            height: 124px;
            display: inline-block;
            cursor: pointer;
            border: solid 2px #fff;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            transition: all 0.2s;
            &.active,
            &:hover {
                border-color: #0396f2;
                transform: scale(1.02);
            }
        }
    }
</style>
<script>
    import ImgUpload from "./ImgUpload";
    import HeaderCreate from "./project/header/create";
    import HeaderArchived from "./project/header/archived";
    import ChatIndex from "./chat/Index";
    import WDrawer from "./iview/WDrawer";
    export default {
        name: 'WHeader',
        components: {WDrawer, ChatIndex, HeaderArchived, HeaderCreate, ImgUpload},
        data() {
            return {
                tabActive: '',

                loadIng: 0,
                userInfo: {},
                userDrawerShow: false,
                userDrawerTab: 'personal',

                formDatum: {
                    userimg: '',
                    nickname: '',
                    profession: ''
                },
                ruleDatum: { },

                formPass: {
                    oldpass: '',
                    newpass: '',
                    checkpass: '',
                },
                rulePass: { },

                formSetting: {
                    bgid: 0,
                },

                chatDrawerShow: false,
            }
        },
        created() {
            this.ruleDatum = {
                nickname: [
                    { required: true, message: this.$L('请输入昵称！'), trigger: 'change' },
                    { type: 'string', min: 2, message: this.$L('昵称长度至少2位！'), trigger: 'change' }
                ]
            };
            this.rulePass = {
                oldpass: [
                    { required: true, message: this.$L('请输入旧密码！'), trigger: 'change' },
                    { type: 'string', min: 6, message: this.$L('密码长度至少6位！'), trigger: 'change' }
                ],
                newpass: [
                    {
                        validator: (rule, value, callback) => {
                            if (value === '') {
                                callback(new Error(this.$L('请输入新密码！')));
                            } else {
                                if (this.formPass.checkpass !== '') {
                                    this.$refs.formPass.validateField('checkpass');
                                }
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    },
                    { type: 'string', min: 6, message: this.$L('密码长度至少6位！'), trigger: 'change' }
                ],
                checkpass: [
                    {
                        validator: (rule, value, callback) => {
                            if (value === '') {
                                callback(new Error(this.$L('请输入确认新密码！')));
                            } else if (value !== this.formPass.newpass) {
                                callback(new Error(this.$L('两次密码输入不一致!')));
                            } else {
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    }
                ],
            };
        },
        mounted() {
            let resCall = () => {
                this.$set(this.formDatum, 'userimg', this.userInfo.userimg)
                this.$set(this.formDatum, 'nickname', this.userInfo.nickname)
                this.$set(this.formDatum, 'profession', this.userInfo.profession)
                this.$set(this.formSetting, 'bgid', this.userInfo.bgid)
            };
            this.userInfo = $A.getUserInfo((res) => {
                this.userInfo = res;
                resCall();
            }, false);
            resCall();
            //
            this.tabActive = this.$route.meta.tabActive;
        },
        watch: {
            '$route' () {
                this.tabActive = this.$route.meta.tabActive;
                this.userDrawerShow = false;
            }
        },
        methods: {
            getBgUrl(id, thumb) {
                id = Math.max(1, parseInt(id));
                return 'url(' + window.location.origin + '/images/bg/' + (thumb ? 'thumb/' : '') + id + '.jpg' + ')';
            },
            tabPage(path) {
                this.goForward({path: '/' + path});
            },
            setRightSelect(act) {
                switch (act) {
                    case 'user':
                        this.userDrawerShow = true;
                        break;

                    case 'out':
                        this.logout();
                        break;
                }
            },
            logout() {
                this.$Modal.confirm({
                    title: this.$L('退出登录'),
                    content: this.$L('您确定要退出登录吗？'),
                    onOk: () => {
                        $A.userLogout();
                    },
                });
            },
            handleSubmit(name) {
                this.$refs[name].validate((valid) => {
                    if (valid) {
                        switch (name) {
                            case "formDatum": {
                                this.loadIng++;
                                $A.aAjax({
                                    url: 'users/editdata',
                                    data: this.formDatum,
                                    complete: () => {
                                        this.loadIng--;
                                    },
                                    success: (res) => {
                                        if (res.ret === 1) {
                                            $A.getUserInfo(true);
                                            this.$Message.success(this.$L('修改成功'));
                                        } else {
                                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                        }
                                    }
                                });
                                break;
                            }
                            case "formPass": {
                                this.loadIng++;
                                $A.aAjax({
                                    url: 'users/editpass',
                                    data: this.formPass,
                                    complete: () => {
                                        this.loadIng--;
                                    },
                                    success: (res) => {
                                        if (res.ret === 1) {
                                            this.userDrawerShow = false;
                                            this.$Message.success(this.$L('修改成功，请重新登录！'));
                                            this.$refs[name].resetFields();
                                            $A.userLogout();
                                        } else {
                                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                        }
                                    }
                                });
                                break;
                            }
                            case "formSetting": {
                                this.loadIng++;
                                $A.aAjax({
                                    url: 'users/editdata',
                                    data: this.formSetting,
                                    complete: () => {
                                        this.loadIng--;
                                    },
                                    success: (res) => {
                                        if (res.ret === 1) {
                                            $A.getUserInfo(true);
                                            this.$Message.success(this.$L('修改成功'));
                                        } else {
                                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                        }
                                    }
                                });
                                break;
                            }
                        }
                    }
                })
            },
            handleReset(name) {
                this.$refs[name].resetFields();
            }
        },
    }
</script>
