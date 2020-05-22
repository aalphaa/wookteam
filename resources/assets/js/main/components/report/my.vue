<template>
    <drawer-tabs-container>
        <div class="report-my">
            <!-- 按钮 -->
            <Button :loading="loadIng > 0" type="primary" icon="md-add" @click="[addDrawerId=0,addDrawerShow=true]">新建汇报</Button>
            <!-- 列表 -->
            <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
            <!-- 分页 -->
            <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>
        </div>
        <Drawer v-model="addDrawerShow" width="70%">
            <report-add :canload="addDrawerShow" :id="addDrawerId" @on-success="addDrawerSuccess"></report-add>
        </Drawer>
        <Modal
            v-model="contentShow"
            :title="contentTitle"
            width="80%"
            :styles="{top: '35px', paddingBottom: '35px'}"
            footerHide>
            <report-content :content="contentText"></report-content>
        </Modal>
    </drawer-tabs-container>
</template>

<style lang="scss" scoped>
    .report-my {
        padding: 0 12px;
        .tableFill {
            margin: 12px 0 20px;
        }
    }
</style>
<script>
    import DrawerTabsContainer from "../DrawerTabsContainer";
    import ReportAdd from "./add";
    import ReportContent from "./content";

    /**
     * 我的汇报
     */
    export default {
        name: 'ReportMy',
        components: {ReportContent, ReportAdd, DrawerTabsContainer},
        props: {
            canload: {
                type: Boolean,
                default: true
            },
        },
        data () {
            return {
                loadYet: false,

                loadIng: 0,

                columns: [],

                lists: [],
                listPage: 1,
                listTotal: 0,
                noDataText: "数据加载中.....",

                addDrawerId: 0,
                addDrawerShow: false,

                contentShow: false,
                contentTitle: '',
                contentText: '内容加载中.....',
            }
        },
        created() {
            this.columns = [{
                "title": "标题",
                "key": 'title',
                "minWidth": 120,
            }, {
                "title": "创建日期",
                "width": 160,
                "align": 'center',
                render: (h, params) => {
                    return h('span', $A.formatDate("Y-m-d H:i:s", params.row.indate));
                }
            }, {
                "title": "类型",
                "key": 'type',
                "width": 80,
                "align": 'center',
            }, {
                "title": "状态",
                "key": 'status',
                "width": 80,
                "align": 'center',
            }, {
                "title": "操作",
                "key": 'action',
                "width": 140,
                "align": 'center',
                render: (h, params) => {
                    let arr = [];
                    arr.push(h('a', {
                        style: {padding: '0 2px', fontSize: '12px'},
                        on: {
                            click: () => {
                                this.contentReport(params.row);
                            }
                        }
                    }, '查看'));
                    arr.push(h('a', {
                        style: {padding: '0 2px', fontSize: '12px'},
                        on: {
                            click: () => {
                                this.addDrawerId = params.row.id;
                                this.addDrawerShow = true
                            }
                        }
                    }, '编辑'));
                    if (params.row.status == '未发送') {
                        arr.push(h('a', {
                            style: {padding: '0 2px', fontSize: '12px'},
                            on: {
                                click: () => {
                                    this.deleteReport(params.row);
                                }
                            }
                        }, '删除'));
                        arr.push(h('a', {
                            style: {padding: '0 2px', fontSize: '12px'},
                            on: {
                                click: () => {
                                    this.sendReport(params.row);
                                }
                            }
                        }, '发送'));
                    }
                    return h('div', arr);
                }
            }];
        },
        mounted() {
            if (this.canload) {
                this.loadYet = true;
                this.getLists(true);
            }
        },

        watch: {
            canload(val) {
                if (val && !this.loadYet) {
                    this.loadYet = true;
                    this.getLists(true);
                }
            }
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
                    url: 'report/my',
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
                        } else {
                            this.lists = [];
                            this.listTotal = 0;
                            this.noDataText = res.msg;
                        }
                    }
                });
            },

            addDrawerSuccess() {
                this.addDrawerShow = false;
                this.getLists(true);
            },

            contentReport(row) {
                this.contentShow = true;
                this.contentTitle = row.title;
                this.contentText = '详细内容加载中.....';
                $A.aAjax({
                    url: 'report/content?id=' + row.id,
                    error: () => {
                        alert(this.$L('网络繁忙，请稍后再试！'));
                        this.contentShow = false;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.contentText = res.data.content;
                        } else {
                            this.contentShow = false;
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                        }
                    }
                });
            },

            sendReport(row) {
                this.$Modal.confirm({
                    title: '发送汇报',
                    content: '你确定要发送汇报吗？',
                    loading: true,
                    onOk: () => {
                        $A.aAjax({
                            url: 'report/template?act=send&id=' + row.id + '&type=' + row.type,
                            error: () => {
                                this.$Modal.remove();
                                alert(this.$L('网络繁忙，请稍后再试！'));
                            },
                            success: (res) => {
                                this.$Modal.remove();
                                this.$set(row, 'status', '已发送');
                                setTimeout(() => {
                                    if (res.ret === 1) {
                                        this.$Message.success(res.msg);
                                    } else {
                                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                    }
                                }, 350);
                            }
                        });
                    }
                });
            },

            deleteReport(row) {
                this.$Modal.confirm({
                    title: '删除汇报',
                    content: '你确定要删除汇报吗？',
                    loading: true,
                    onOk: () => {
                        $A.aAjax({
                            url: 'report/template?act=delete&id=' + row.id + '&type=' + row.type,
                            error: () => {
                                this.$Modal.remove();
                                alert(this.$L('网络繁忙，请稍后再试！'));
                            },
                            success: (res) => {
                                this.$Modal.remove();
                                this.lists.some((item, index) => {
                                    if (item.id == row.id) {
                                        this.lists.splice(index, 1);
                                        return true;
                                    }
                                })
                                setTimeout(() => {
                                    if (res.ret === 1) {
                                        this.$Message.success(res.msg);
                                    } else {
                                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                    }
                                }, 350);
                            }
                        });
                    }
                });
            }
        }
    }
</script>
