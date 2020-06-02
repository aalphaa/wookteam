<template>
    <drawer-tabs-container>
        <div class="report-my">
            <!-- 搜索 -->
            <Row class="sreachBox">
                <div class="item">
                    <div class="item-2">
                        <sreachTitle :val="keys.type">{{$L('类型')}}</sreachTitle>
                        <Select v-model="keys.type" :placeholder="$L('全部')">
                            <Option value="">{{$L('全部')}}</Option>
                            <Option value="日报">{{$L('日报')}}</Option>
                            <Option value="周报">{{$L('周报')}}</Option>
                        </Select>
                    </div>
                    <div class="item-2">
                        <sreachTitle :val="keys.indate">{{$L('日期')}}</sreachTitle>
                        <Date-picker v-model="keys.indate" type="daterange" placement="bottom" :placeholder="$L('日期范围')"></Date-picker>
                    </div>
                </div>
                <div class="item item-button">
                    <Button type="text" v-if="$A.objImplode(keys)!=''" @click="sreachTab(true)">{{$L('取消筛选')}}</Button>
                    <Button type="primary" icon="md-search" :loading="loadIng > 0" @click="sreachTab">{{$L('搜索')}}</Button>
                </div>
            </Row>
            <!-- 按钮 -->
            <Row class="butBox" style="float:left;margin-top:-32px;">
                <Button :loading="loadIng > 0" type="primary" icon="md-add" @click="[addDrawerId=0,addDrawerShow=true]">{{$L('新建汇报')}}</Button>
            </Row>
            <!-- 列表 -->
            <Table class="tableFill" ref="tableRef" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" stripe></Table>
            <!-- 分页 -->
            <Page class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>
        </div>
        <WDrawer v-model="addDrawerShow" maxWidth="1000">
            <report-add :canload="addDrawerShow" :id="addDrawerId" @on-success="addDrawerSuccess"></report-add>
        </WDrawer>
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
    import WDrawer from "../iview/WDrawer";

    /**
     * 我的汇报
     */
    export default {
        name: 'ReportMy',
        components: {WDrawer, ReportContent, ReportAdd, DrawerTabsContainer},
        props: {
            canload: {
                type: Boolean,
                default: true
            },
        },
        data () {
            return {
                keys: {},
                sorts: {key:'', order:''},

                loadYet: false,

                loadIng: 0,

                columns: [],

                lists: [],
                listPage: 1,
                listTotal: 0,
                noDataText: "",

                addDrawerId: 0,
                addDrawerShow: false,

                contentShow: false,
                contentTitle: '',
                contentText: '',
            }
        },
        created() {
            this.noDataText = this.$L("数据加载中.....");
            this.contentText = this.$L("内容加载中.....");
            this.columns = [{
                "title": this.$L("标题"),
                "key": 'title',
                "minWidth": 120,
            }, {
                "title": this.$L("类型"),
                "key": 'type',
                "minWidth": 80,
                "maxWidth": 120,
                "align": 'center',
            }, {
                "title": this.$L("状态"),
                "key": 'status',
                "minWidth": 80,
                "maxWidth": 120,
                "align": 'center',
            }, {
                "title": this.$L("创建日期"),
                "minWidth": 160,
                "maxWidth": 200,
                "align": 'center',
                "sortable": true,
                render: (h, params) => {
                    return h('span', $A.formatDate("Y-m-d H:i:s", params.row.indate));
                }
            }, {
                "title": " ",
                "key": 'action',
                "align": 'right',
                "width": 120,
                render: (h, params) => {
                    if (!params.row.id) {
                        return null;
                    }
                    return h('div', [
                        h('Tooltip', {
                            props: { content: this.$L('查看'), transfer: true, delay: 600 },
                            style: { position: 'relative' },
                        }, [h('Icon', {
                            props: { type: 'md-eye', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                            on: {
                                click: () => {
                                    this.contentReport(params.row);
                                }
                            }
                        })]),
                        h('Tooltip', {
                            props: { content: this.$L('编辑'), transfer: true, delay: 600 }
                        }, [h('Icon', {
                            props: { type: 'md-create', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                            on: {
                                click: () => {
                                    this.addDrawerId = params.row.id;
                                    this.addDrawerShow = true
                                }
                            }
                        })]),
                        h('Tooltip', {
                            props: { content: this.$L('发送'), transfer: true, delay: 600 }
                        }, [h('Icon', {
                            props: { type: 'md-send', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                            on: {
                                click: () => {
                                    this.sendReport(params.row);
                                }
                            }
                        })]),
                        h('Tooltip', {
                            props: { content: this.$L('删除'), transfer: true, delay: 600 }
                        }, [h('Icon', {
                            props: { type: 'md-trash', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                            on: {
                                click: () => {
                                    this.deleteReport(params.row);
                                }
                            }
                        })]),
                    ]);
                },
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
            sreachTab(clear) {
                if (clear === true) {
                    this.keys = {};
                }
                this.getLists(true);
            },

            sortChange(info) {
                this.sorts = {key:info.key, order:info.order};
                this.getLists(true);
            },

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
                let whereData = $A.date2string($A.cloneData(this.keys));
                whereData.page = Math.max(this.listPage, 1);
                whereData.pagesize = Math.max($A.runNum(this.listPageSize), 10);
                whereData.sorts = $A.cloneData(this.sorts);
                this.loadIng++;
                this.noDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'report/my',
                    data: whereData,
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.noDataText = this.$L("数据加载失败！");
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.lists = res.data.lists;
                            this.listTotal = res.data.total;
                            this.noDataText = this.$L("没有相关的数据");
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
                this.contentText = this.$L('详细内容加载中.....');
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
                    title: this.$L('发送汇报'),
                    content: this.$L('你确定要发送汇报吗？'),
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
                    title: this.$L('删除汇报'),
                    content: this.$L('你确定要删除汇报吗？'),
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
