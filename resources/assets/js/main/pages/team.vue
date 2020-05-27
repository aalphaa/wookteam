<template>
    <div class="w-main team">

        <v-title>{{$L('团队')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="team"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <div class="page-nav-left">
                        <span><i class="ft icon">&#xE90D;</i> {{$L('团队成员')}}</span>
                        <div v-if="loadIng > 0" class="page-nav-loading"><w-loading></w-loading></div>
                        <div v-else class="page-nav-refresh"><em @click="getLists(true)">刷新</em></div>
                    </div>
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover" @click="addShow=true"><i class="ft icon">&#xE740;</i> {{$L('添加团队成员')}}</span>
                </div>
            </div>
        </div>

        <w-content>
            <div class="team-main">
                <div class="team-body">
                    <!-- 列表 -->
                    <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
                    <!-- 分页 -->
                    <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total></Page>
                </div>
            </div>
        </w-content>

        <Modal
            v-model="addShow"
            :title="$L('添加团队成员')"
            :closable="false"
            :mask-closable="false">
            <Form ref="add" :model="formAdd" :rules="ruleAdd" :label-width="80">
                <FormItem prop="userimg" :label="$L('头像')">
                    <ImgUpload v-model="formAdd.userimg"></ImgUpload>
                </FormItem>
                <FormItem prop="nickname" :label="$L('昵称')">
                    <Input type="text" v-model="formAdd.nickname"></Input>
                </FormItem>
                <FormItem prop="profession" :label="$L('职位/职称')">
                    <Input v-model="formAdd.profession"></Input>
                </FormItem>
                <FormItem prop="username" :label="$L('用户名')">
                    <Input type="text" v-model="formAdd.username" :placeholder="$L('添加后不可修改')"></Input>
                </FormItem>
                <FormItem prop="userpass" :label="$L('登录密码')">
                    <Input type="password" v-model="formAdd.userpass" :placeholder="$L('最少6位数')"></Input>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onAdd">{{$L('添加')}}</Button>
            </div>
        </Modal>

    </div>
</template>

<style lang="scss" scoped>
    .team {
        .team-main {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            padding: 15px;
            .team-body {
                display: flex;
                flex-direction: column;
                width: 100%;
                height: 100%;
                min-height: 500px;
                background: #fefefe;
                border-radius: 3px;
                .tableFill {
                    margin: 20px;
                    background-color: #ffffff;
                }
            }
        }
    }
</style>
<script>
    import WHeader from "../components/WHeader";
    import WContent from "../components/WContent";
    import ImgUpload from "../components/ImgUpload";
    export default {
        components: {ImgUpload, WContent, WHeader},
        data () {
            return {
                loadIng: 0,

                userInfo: {},

                columns: [],

                lists: [],
                listPage: 1,
                listTotal: 0,
                noDataText: "数据加载中.....",

                addShow: false,
                formAdd: {
                    userimg: '',
                    profession: '',
                    username: '',
                    nickname: '',
                    userpass: ''
                },
                ruleAdd: {}
            }
        },
        created() {
            let isAdmin = $A.identity('admin');
            this.columns = [{
                "title": "头像",
                "minWidth": 60,
                "maxWidth": 100,
                render: (h, params) => {
                    return h('img', {
                        style: {
                            width: "32px",
                            height: "32px",
                            verticalAlign: "middle",
                            objectFit: "cover",
                            borderRadius: "50%"
                        },
                        attrs: {
                            src: params.row.userimg
                        },
                    });
                }
            }, {
                "title": "昵称",
                "minWidth": 80,
                "ellipsis": true,
                render: (h, params) => {
                    return h('span', params.row.nickname || '-');
                }
            }, {
                "title": "用户名",
                "key": 'username',
                "minWidth": 80,
                "ellipsis": true,
            }, {
                "title": "职位/职称",
                "minWidth": 100,
                "ellipsis": true,
                render: (h, params) => {
                    return h('span', params.row.profession || '-');
                }
            }, {
                "title": "加入时间",
                "width": 160,
                render: (h, params) => {
                    return h('span', $A.formatDate("Y-m-d H:i:s", params.row.regdate));
                }
            }, {
                "title": "操作",
                "key": 'action',
                "width": isAdmin ? 160 : 80,
                "align": 'center',
                render: (h, params) => {
                    let array = [];
                    array.push(h('Button', {
                        props: {
                            type: 'primary',
                            size: 'small'
                        },
                        style: {
                            fontSize: '12px'
                        },
                        on: {
                            click: () => {
                                this.$Modal.info({
                                    title: '会员信息',
                                    content: `<p>昵称: ${params.row.nickname || '-'}</p><p>职位/职称: ${params.row.profession || '-'}</p>`
                                });
                            }
                        }
                    }, '查看'));
                    if (isAdmin) {
                        array.push(h('Button', {
                            props: {
                                type: 'warning',
                                size: 'small'
                            },
                            style: {
                                fontSize: '12px',
                                marginLeft: '5px'
                            },
                            on: {
                                click: () => {
                                    this.$Modal.confirm({
                                        title: '删除团队成员',
                                        content: '你确定要删除此团队成员吗？',
                                        loading: true,
                                        onOk: () => {
                                            $A.aAjax({
                                                url: 'users/team/delete?id=' + params.row.id,
                                                error: () => {
                                                    this.$Modal.remove();
                                                    alert(this.$L('网络繁忙，请稍后再试！'));
                                                },
                                                success: (res) => {
                                                    this.$Modal.remove();
                                                    this.getLists();
                                                    setTimeout(() => {
                                                        if (res.ret === 1) {
                                                            this.$Message.success(res.msg);
                                                        }else{
                                                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                                        }
                                                    }, 350);
                                                }
                                            });
                                        }
                                    });
                                }
                            }
                        }, '删除'));
                    }
                    return h('div', array);
                }
            }];
            //
            this.ruleAdd = {
                username: [
                    { required: true, message: this.$L('请填写用户名！'), trigger: 'change' },
                    { type: 'string', min: 2, message: this.$L('用户名长度至少2位！'), trigger: 'change' }
                ],
                userpass: [
                    { required: true, message: this.$L('请填写登录密码！'), trigger: 'change' },
                    { type: 'string', min: 6, message: this.$L('密码长度至少6位！'), trigger: 'change' }
                ]
            };
        },
        mounted() {
            this.getLists(true);
            this.userInfo = $A.getUserInfo((res, isLogin) => {
                if (this.userInfo.id != res.id) {
                    this.userInfo = res;
                    isLogin && this.getLists(true);
                }
            }, false);
        },
        deactivated() {
            this.addShow = false;
        },
        methods: {
            setPage(page) {
                this.listPage = page;
                this.getLists();
            },

            setPageSize(size) {
                if (Math.max($A.runNum(this.listPageSize), 10) != size) {
                    this.listPageSize = size;
                    this.getLists();
                }
            },

            getLists(resetLoad) {
                if (resetLoad === true) {
                    this.listPage = 1;
                }
                this.loadIng++;
                this.noDataText = "数据加载中.....";
                $A.aAjax({
                    url: 'users/team/lists',
                    data: {
                        page: Math.max(this.listPage, 1),
                        pagesize: Math.max($A.runNum(this.listPageSize), 10),
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.noDataText = "数据加载失败！";
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.lists = res.data.lists;
                            this.listTotal = res.data.total;
                            this.noDataText = "没有相关的数据";
                        }else{
                            this.lists = [];
                            this.listTotal = 0;
                            this.noDataText = res.msg;
                        }
                    }
                });
            },

            onAdd() {
                this.$refs.add.validate((valid) => {
                    if (valid) {
                        this.loadIng++;
                        $A.aAjax({
                            url: 'users/team/add',
                            data: this.formAdd,
                            complete: () => {
                                this.loadIng--;
                            },
                            success: (res) => {
                                if (res.ret === 1) {
                                    this.addShow = false;
                                    this.$Message.success(res.msg);
                                    this.$refs.add.resetFields();
                                    //
                                    this.getLists(true);
                                }else{
                                    this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                }
                            }
                        });
                    }
                });
            }
        },
    }
</script>
