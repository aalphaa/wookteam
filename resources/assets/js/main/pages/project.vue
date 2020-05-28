<template>
    <div class="w-main project">

        <v-title>{{$L('项目')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="project"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <div class="page-nav-left">
                        <span class="hover" @click="addShow=true"><i class="ft icon">&#xE740;</i> {{$L('新建项目')}}</span>
                        <div v-if="loadIng > 0" class="page-nav-loading"><w-loading></w-loading></div>
                        <div v-else class="page-nav-refresh"><em @click="getLists(true)">{{$L('刷新')}}</em></div>
                    </div>
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover" @click="handleProject('myjoin', null)"><i class="ft icon">&#xE75E;</i> {{$L('参与的项目')}}</span>
                    <span class="ft hover" @click="handleProject('myfavor', null)"><i class="ft icon">&#xE720;</i> {{$L('收藏的项目')}}</span>
                    <span class="ft hover" @click="handleProject('mycreate', null)"><i class="ft icon">&#xE764;</i> {{$L('我管理的项目')}}</span>
                </div>
            </div>
        </div>

        <w-content>
            <!-- 列表 -->
            <ul class="project-list">
                <li v-for="item in lists">
                    <div class="project-item">
                        <div class="project-head">
                            <div v-if="item.loadIng === true" class="project-loading">
                                <w-loading></w-loading>
                            </div>
                            <div class="project-title" @click="handleProject('open', item)">{{item.title}}</div>
                            <div class="project-setting">
                                <Dropdown class="right-info" trigger="click" @on-click="handleProject($event, item)" transfer>
                                    <Icon class="project-setting-icon" type="md-settings" size="16"/>
                                    <Dropdown-menu slot="list">
                                        <Dropdown-item name="open">{{$L('打开')}}</Dropdown-item>
                                        <Dropdown-item name="favor">{{$L('收藏')}}</Dropdown-item>
                                        <Dropdown-item v-if="item.isowner" name="rename">{{$L('重命名')}}</Dropdown-item>
                                        <Dropdown-item v-if="item.isowner" name="transfer">{{$L('移交项目')}}</Dropdown-item>
                                        <Dropdown-item v-if="item.isowner" name="delete">{{$L('删除')}}</Dropdown-item>
                                        <Dropdown-item v-else name="out">{{$L('退出')}}</Dropdown-item>
                                    </Dropdown-menu>
                                </Dropdown>
                            </div>
                        </div>
                        <div class="project-num" @click="handleProject('open', item)">
                            <div class="project-complete"><em>{{item.complete}}</em>{{$L('已完成数')}}</div>
                            <div class="project-num-line"></div>
                            <div class="project-unfinished"><em>{{item.unfinished}}</em>{{$L('未完成数')}}</div>
                        </div>
                        <div class="project-bottom">
                            <div class="project-iconbtn" @click.stop="handleProject('archived', item)">
                                <Icon class="project-iconbtn-icon1" type="md-filing" size="24" />
                                <div class="project-iconbtn-text">{{$L('已归档任务')}}</div>
                            </div>
                            <div class="project-iconbtn" @click.stop="handleProject('statistics', item)">
                                <Icon class="project-iconbtn-icon3" type="md-stats" size="24" />
                                <div class="project-iconbtn-text">{{$L('项目统计')}}</div>
                            </div>
                            <div class="project-iconbtn" @click.stop="handleProject('member', item)">
                                <Icon class="project-iconbtn-icon2" type="md-people" size="24" />
                                <div class="project-iconbtn-text">{{$L('成员管理')}}</div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
            <!-- 分页 -->
            <Page v-if="listTotal > 0" class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total></Page>
        </w-content>

        <Modal
            v-model="addShow"
            :title="$L('新建项目')"
            :closable="false"
            :mask-closable="false">
            <Form ref="add" :model="formAdd" :rules="ruleAdd" :label-width="80">
                <FormItem prop="title" :label="$L('项目名称')">
                    <Input type="text" v-model="formAdd.title"></Input>
                </FormItem>
                <FormItem prop="labels" :label="$L('项目模板')">
                    <Select v-model="formAdd.template" @on-change="(res) => {$set(formAdd, 'labels', labelLists[res].value)}">
                        <Option v-for="(item, index) in labelLists" :value="index" :key="index">{{ item.label }}</Option>
                    </Select>
                </FormItem>
                <FormItem :label="$L('项目流程')">
                    <div style="line-height:38px">
                        <span v-for="(item, index) in formAdd.labels">
                            <span v-if="index > 0">&gt;</span>
                            <Tag @on-close="() => { formAdd.labels.splice(index, 1)}" closable size="large" color="primary">{{item}}</Tag>
                        </span>
                    </div>
                    <div v-if="formAdd.labels.length > 0" style="margin-top:4px;"></div>
                    <div style="margin-bottom:-16px">
                        <Button icon="ios-add" type="dashed" @click="addLabels">{{$L('添加流程')}}</Button>
                    </div>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onAdd">{{$L('添加')}}</Button>
            </div>
        </Modal>

        <Drawer v-model="projectDrawerShow" width="75%">
            <Tabs v-if="projectDrawerShow" v-model="projectDrawerTab">
                <TabPane :label="$L('已归档任务')" name="archived">
                    <project-archived :canload="projectDrawerShow && projectDrawerTab == 'archived'" :projectid="handleProjectId"></project-archived>
                </TabPane>
                <TabPane :label="$L('项目统计')" name="statistics">
                    <project-statistics :canload="projectDrawerShow && projectDrawerTab == 'statistics'" :projectid="handleProjectId"></project-statistics>
                </TabPane>
                <TabPane :label="$L('成员管理')" name="member">
                    <project-users :canload="projectDrawerShow && projectDrawerTab == 'member'" :projectid="handleProjectId"></project-users>
                </TabPane>
            </Tabs>
        </Drawer>

        <Drawer v-model="projectListDrawerShow" width="50%">
            <Tabs v-if="projectListDrawerShow" v-model="projectListDrawerTab">
                <TabPane :label="$L('参与的项目')" name="myjoin">
                    <project-my-join :canload="projectListDrawerShow && projectListDrawerTab == 'myjoin'"></project-my-join>
                </TabPane>
                <TabPane :label="$L('收藏的项目')" name="myfavor">
                    <project-my-favor :canload="projectListDrawerShow && projectListDrawerTab == 'myfavor'"></project-my-favor>
                </TabPane>
                <TabPane :label="$L('管理的项目')" name="mycreate">
                    <project-my-manage :canload="projectListDrawerShow && projectListDrawerTab == 'mycreate'"></project-my-manage>
                </TabPane>
            </Tabs>
        </Drawer>
    </div>
</template>

<style lang="scss" scoped>
    .project {
        ul.project-list {
            padding: 5px;
            max-width: 2000px;
            li {
                float: left;
                width: 25%;
                display: flex;
                @media (max-width: 1400px) {
                    width: 33.33%;
                }
                @media (max-width: 1080px) {
                    width: 50%;
                }
                @media (max-width: 640px) {
                    width: 100%;
                }
                .project-item {
                    flex: 1;
                    margin: 10px;
                    width: 100%;
                    height: 280px;
                    padding: 20px;
                    background-color: #ffffff;
                    border-radius: 4px;
                    display: flex;
                    flex-direction: column;
                    .project-head{
                        display: flex;
                        flex-direction: row;
                        .project-loading {
                            width: 18px;
                            height: 18px;
                            margin-right: 6px;
                            margin-top: 3px;
                        }
                        .project-title{
                            flex: 1;
                            font-size: 16px;
                            padding-right: 6px;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            white-space:nowrap;
                            color: #333333;
                            cursor: pointer;
                        }
                        .project-setting{
                            width: 30px;
                            text-align: right;
                            .project-setting-icon {
                                cursor: pointer;
                                color: #333333;
                                &:hover {
                                    color: #0396f2;
                                }
                            }
                        }
                    }
                    .project-num {
                        flex: 1;
                        padding: 24px 0;
                        display: flex;
                        flex-direction: row;
                        align-items: center;
                        cursor: pointer;
                        .project-complete,
                        .project-unfinished {
                            flex: 1;
                            text-align: center;
                            font-size: 14px;
                            color: #999999;
                            em {
                                display: block;
                                font-size: 32px;
                                color: #0396f2;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                                max-width: 120px;
                                margin: 0 auto;
                            }
                        }
                        .project-num-line {
                            width: 1px;
                            height: 90%;
                            background-color: #e8e8e8;
                        }
                    }
                    .project-bottom {
                        display: flex;
                        flex-direction: row;
                        align-items: center;
                        border-top: 1px solid #efefef;
                        padding: 6px 0;
                        cursor: default;
                        .project-iconbtn {
                            flex: 1;
                            text-align: center;
                            cursor: pointer;
                            &:hover {
                                .project-iconbtn-text {
                                    color: #0396f2;
                                }
                            }
                            .project-iconbtn-icon1 {
                                margin: 12px 0;
                                color: #ff7a7a;
                            }
                            .project-iconbtn-icon2 {
                                margin: 12px 0;
                                color: #764df8;
                            }
                            .project-iconbtn-icon3 {
                                margin: 12px 0;
                                color: #ffca65;
                            }
                            .project-iconbtn-text {
                                color: #999999;
                            }
                        }
                    }
                }
            }
            &:before,
            &:after {
                display: table;
                content: "";
            }
            &:after {
                clear: both;
            }
        }
    }
</style>
<script>
    import WHeader from "../components/WHeader";
    import WContent from "../components/WContent";
    import WLoading from "../components/WLoading";
    import ProjectArchived from "../components/project/archived";
    import ProjectUsers from "../components/project/users";
    import ProjectStatistics from "../components/project/statistics";
    import ProjectMyFavor from "../components/project/my/favor";
    import ProjectMyJoin from "../components/project/my/join";
    import ProjectMyManage from "../components/project/my/manage";
    import Project from "../mixins/project";
    export default {
        components: {
            ProjectMyManage,
            ProjectMyJoin,
            ProjectMyFavor, ProjectStatistics, ProjectUsers, ProjectArchived, WLoading, WContent, WHeader},
        mixins: [
            Project
        ],
        data () {
            return {
                loadIng: 0,

                userInfo: {},

                addShow: false,
                formAdd: {
                    title: '',
                    labels: [],
                    template: 0,
                },
                ruleAdd: {},

                labelLists: [],

                lists: [],
                listPage: 1,
                listTotal: 0,

                projectDrawerShow: false,
                projectDrawerTab: 'archived',

                projectListDrawerShow: false,
                projectListDrawerTab: 'myjoin',

                handleProjectId: 0,
            }
        },
        created() {
            this.labelLists = [{
                label: this.$L('空白模板'),
                value: [],
            }, {
                label: this.$L('软件开发'),
                value: [this.$L('产品规划'),this.$L('前端开发'),this.$L('后端开发'),this.$L('测试'),this.$L('发布'),this.$L('其它')],
            }, {
                label: this.$L('产品开发'),
                value: [this.$L('产品计划'), this.$L('正在设计'), this.$L('正在研发'), this.$L('测试'), this.$L('准备发布'), this.$L('发布成功')],
            }];
            this.ruleAdd = {
                title: [
                    { required: true, message: this.$L('请填写项目名称！'), trigger: 'change' },
                    { type: 'string', min: 2, message: this.$L('项目名称至少2个字！'), trigger: 'change' }
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
            //
            $A.setOnTaskInfoListener('pages/project',(act, detail) => {
                switch (act) {
                    case 'deleteproject':   // 删除项目
                    case 'deletelabel':     // 删除分类
                        this.lists.some((item) => {
                            if (item.id == detail.projectid) {
                                this.getLists(true);
                                return true;
                            }
                        });
                        break;
                    case "complete":        // 标记完成
                        this.lists.some((item) => {
                            if (item.id == detail.projectid) {
                                item.complete++;
                                item.unfinished--;
                                return true;
                            }
                        });
                        break;
                    case "unfinished":      // 标记未完成
                        this.lists.some((item) => {
                            if (item.id == detail.projectid) {
                                item.complete--;
                                item.unfinished++;
                                return true;
                            }
                        });
                        break;
                }
            }, true);
        },
        deactivated() {
            this.addShow = false;
            this.projectDrawerShow = false;
            this.projectListDrawerShow = false;
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
                $A.aAjax({
                    url: 'project/lists',
                    data: {
                        page: Math.max(this.listPage, 1),
                        pagesize: Math.max($A.runNum(this.listPageSize), 10),
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.lists = res.data.lists;
                            this.listTotal = res.data.total;
                        }else{
                            this.lists = [];
                            this.listTotal = 0;
                        }
                    }
                });
            },

            addLabels() {
                this.labelsValue = "";
                this.$Modal.confirm({
                    render: (h) => {
                        return h('div', [
                            h('div', {
                                style: {
                                    fontSize: '16px',
                                    fontWeight: '500',
                                    marginBottom: '20px',
                                }
                            }, this.$L('添加流程')),
                            h('Input', {
                                props: {
                                    value: this.labelsValue,
                                    autofocus: true,
                                    placeholder: this.$L('请输入流程名称，多个可用空格分隔。')
                                },
                                on: {
                                    input: (val) => {
                                        this.labelsValue = val;
                                    }
                                }
                            })
                        ])
                    },
                    onOk: () => {
                        if (this.labelsValue) {
                            let array = $A.trim(this.labelsValue).split(" ");
                            array.forEach((name) => {
                                if ($A.trim(name)) {
                                    this.formAdd.labels.push($A.trim(name));
                                }
                            });
                        }
                    },
                })
            },

            onAdd() {
                this.$refs.add.validate((valid) => {
                    if (valid) {
                        this.loadIng++;
                        $A.aAjax({
                            url: 'project/add',
                            data: this.formAdd,
                            complete: () => {
                                this.loadIng--;
                            },
                            success: (res) => {
                                if (res.ret === 1) {
                                    this.addShow = false;
                                    this.$Message.success(res.msg);
                                    this.$refs.add.resetFields();
                                    this.$set(this.formAdd, 'template', 0);
                                    //
                                    this.getLists(true);
                                } else {
                                    this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                }
                            }
                        });
                    }
                });
            },

            handleProject(event, item) {
                if (item) {
                    this.handleProjectId = item.id;
                }
                switch (event) {
                    case 'favor': {
                        this.favorProject('add', item.id);
                        break;
                    }
                    case 'rename': {
                        this.renameProject(item);
                        break;
                    }
                    case 'transfer': {
                        this.transferProject(item);
                        break;
                    }
                    case 'delete': {
                        this.deleteProject(item.id, () => {
                            this.getLists();
                        });
                        break;
                    }
                    case 'out': {
                        this.outProject(item.id, () => {
                            this.getLists();
                        });
                        break;
                    }

                    case 'open': {
                        this.openProject(item.id, item);
                        break;
                    }
                    case 'archived':
                    case 'member':
                    case 'statistics': {
                        this.projectDrawerShow = true;
                        this.projectDrawerTab = event;
                        break;
                    }
                    case 'myjoin':
                    case 'myfavor':
                    case 'mycreate': {
                        this.projectListDrawerShow = true;
                        this.projectListDrawerTab = event;
                        break;
                    }
                }
            },

            renameProject(item) {
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
                            }, this.$L('重命名项目')),
                            h('Input', {
                                props: {
                                    value: this.renameValue,
                                    autofocus: true,
                                    placeholder: this.$L('请输入新的项目名称')
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
                                url: 'project/rename',
                                data: {
                                    projectid: item.id,
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

            transferProject(item) {
                this.transferValue = "";
                this.$Modal.confirm({
                    render: (h) => {
                        return h('div', [
                            h('div', {
                                style: {
                                    fontSize: '16px',
                                    fontWeight: '500',
                                    marginBottom: '20px',
                                }
                            }, this.$L('移交项目')),
                            h('UserInput', {
                                props: {
                                    value: this.transferValue,
                                    nousername: item.username,
                                    placeholder: this.$L('请输入昵称/用户名搜索')
                                },
                                on: {
                                    input: (val) => {
                                        this.transferValue = val;
                                    }
                                }
                            })
                        ])
                    },
                    loading: true,
                    onOk: () => {
                        if (this.transferValue) {
                            this.$set(item, 'loadIng', true);
                            let username = this.transferValue;
                            $A.aAjax({
                                url: 'project/transfer',
                                data: {
                                    projectid: item.id,
                                    username: username,
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
                                    this.getLists();
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
        },
    }
</script>
