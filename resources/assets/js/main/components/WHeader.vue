<template>
    <div class="w-header">
        <div class="w-header-row">
            <div class="w-header-row-left">
                <ul>
                    <li :class="value==='todo'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('todo')"><i class="ft icon">&#xe89e;</i>{{$L('待办')}}</a>
                    </li><li :class="value==='project'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('project')"><i class="ft icon">&#xe6b8;</i>{{$L('项目')}}</a>
                    </li><li :class="value==='docs'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('docs')"><i class="ft icon">&#xe915;</i>{{$L('知识库')}}</a>
                    </li><li :class="value==='team'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('team')"><i class="ft icon">&#xe90d;</i>{{$L('团队')}}</a>
                    </li>
                </ul>
            </div>
            <div class="w-header-row-flex"></div>
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
                <Dropdown class="right-info" trigger="click" @on-click="setLanguage" transfer>
                    <div>
                        <Icon class="right-globe" type="md-globe" size="24"/>
                        <Icon type="md-arrow-dropdown"/>
                    </div>
                    <Dropdown-menu slot="list">
                        <Dropdown-item name="zh" :selected="getLanguage() === 'zh'">中文</Dropdown-item>
                        <Dropdown-item name="en" :selected="getLanguage() === 'en'">English</Dropdown-item>
                    </Dropdown-menu>
                </Dropdown>
            </div>
        </div>
        <Drawer v-model="userDrawerShow" width="70%">
            <Tabs v-model="userDrawerTab">
                <TabPane :label="$L('个人资料')" name="personal">
                    <Form ref="formDatum" :model="formDatum" :rules="ruleDatum" :label-width="80">
                        <FormItem :label="$L('头像')" prop="userimg">
                            <ImgUpload v-model="formDatum.userimg"></ImgUpload>
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
                <!--<TabPane :label="$L('偏好设置')" name="setting"></TabPane>-->
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
        </Drawer>
    </div>
</template>

<style lang="scss" scoped>
    .w-header {
        z-index: 15;
        height: 40px;
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
                max-width: 50%;
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
            .w-header-row-flex {
                flex: 1;
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
                    .right-globe {
                        vertical-align: top;
                        margin-top: 8px;
                    }
                }
            }
        }
    }
</style>
<script>
    import ImgUpload from "./ImgUpload";
    import HeaderCreate from "./project/header/create";
    import HeaderArchived from "./project/header/archived";
    export default {
        name: 'WHeader',
        components: {HeaderArchived, HeaderCreate, ImgUpload},
        props: {
            value: {
            },
        },
        data() {
            return {
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
                rulePass: { }
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
            this.userInfo = $A.getUserInfo((res) => {
                this.userInfo = res;
                this.$set(this.formDatum, 'userimg', res.userimg)
                this.$set(this.formDatum, 'nickname', res.nickname)
                this.$set(this.formDatum, 'profession', res.profession)
            }, false);
        },
        methods: {
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
                                            this.$Message.success(this.$L('修改成功，请重新登录！'));
                                            this.userDrawerShow = false;
                                            this.$refs[name].resetFields();
                                            $A.userLogout();
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
