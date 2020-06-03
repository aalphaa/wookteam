<template>
    <drawer-tabs-container>
        <div class="project-task-file">

            <!-- 搜索 -->
            <Row v-if="!simple" class="sreachBox">
                <div class="item">
                    <div class="item-2">
                        <sreachTitle :val="keys.name">{{$L('文件名')}}</sreachTitle>
                        <Input v-model="keys.name" :placeholder="$L('关键词')"/>
                    </div>
                    <div class="item-2">
                        <sreachTitle :val="keys.username">{{$L('上传者')}}</sreachTitle>
                        <Input v-model="keys.username" :placeholder="$L('用户名')"/>
                    </div>
                </div>
                <div class="item item-button">
                    <Button type="text" v-if="$A.objImplode(keys)!=''" @click="sreachTab(true)">{{$L('取消筛选')}}</Button>
                    <Button type="primary" icon="md-search" :loading="loadIng > 0" @click="sreachTab">{{$L('搜索')}}</Button type="primary">
                </div>
            </Row>

            <!-- 按钮 -->
            <Row class="butBox" :style="`float:left;margin-top:-32px;${simple?'display:none':''}`">
                <Upload
                    name="files"
                    ref="upload"
                    :action="actionUrl"
                    :data="params"
                    multiple
                    :format="uploadFormat"
                    :show-upload-list="false"
                    :max-size="10240"
                    :on-success="handleSuccess"
                    :on-format-error="handleFormatError"
                    :on-exceeded-size="handleMaxSize">
                    <Button :loading="loadIng > 0" type="primary" icon="ios-cloud-upload-outline" @click="">{{$L('上传文件')}}</Button>
                </Upload>
            </Row>

            <!-- 列表 -->
            <Table class="tableFill" ref="tableRef" :size="!simple?'default':'small'" :columns="columns" :data="lists" :loading="loadIng > 0" :no-data-text="noDataText" @on-sort-change="sortChange" stripe></Table>

            <!-- 分页 -->
            <Page v-if="lastPage > 1 || !simple" :simple="simple" class="pageBox" :total="listTotal" :current="listPage" :disabled="loadIng > 0" @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]" placement="top" show-elevator show-sizer show-total transfer></Page>

        </div>
    </drawer-tabs-container>
</template>
<style lang="scss" scoped>
    .project-task-file {
        margin: 0 12px;
        .tableFill {
            margin: 12px 0 20px;
        }
    }
</style>

<script>
    import Vue from 'vue'
    import VueClipboard from 'vue-clipboard2'
    import DrawerTabsContainer from "../../DrawerTabsContainer";

    Vue.use(VueClipboard)

    export default {
        name: 'ProjectTaskFiles',
        components: {DrawerTabsContainer},
        props: {
            projectid: {
                default: 0
            },
            taskid: {
                default: 0
            },
            canload: {
                type: Boolean,
                default: true
            },
            simple: {
                type: Boolean,
                default: false
            },
        },
        data() {
            return {
                keys: {},
                sorts: {key:'', order:''},

                loadYet: false,

                loadIng: 0,

                columns: [],

                lists: [],
                listPage: 1,
                listTotal: 0,
                lastPage: 0,
                noDataText: "",

                uploadFormat: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
                actionUrl: $A.aUrl('project/files/upload'),
                params: {'token': $A.token(), taskid:this.taskid, projectid:this.projectid},
                uploadList: [],
            }
        },

        created() {
            this.noDataText = this.$L("数据加载中.....");
            let columns = [];
            columns.push({
                "title": "",
                "width": 60,
                render: (h, params) => {
                    if (!params.row.thumb) {
                        return h('WLoading', {
                            style: {
                                width: "24px",
                                height: "24px",
                                verticalAlign: "middle",
                            },
                        });
                    }
                    return h('img', {
                        style: {
                            width: "28px",
                            height: "28px",
                            verticalAlign: "middle",
                        },
                        attrs: {
                            src: params.row.thumb
                        },
                    });
                }
            });
            columns.push({
                "title": this.$L("文件名"),
                "key": 'name',
                "minWidth": 100,
                "tooltip": true,
                "sortable": true,
                render: (h, params) => {
                    let arr = [h('span', params.row.name)];
                    if (params.row.showProgress === true) {
                        arr.push(h('Progress', {
                            style: {
                                display: 'block',
                                marginTop: '-3px'
                            },
                            props: {
                                percent: params.row.percentage || 100,
                            },
                        }));
                    }
                    return h('div', arr);
                },
            });
            columns.push({
                "title": this.$L("大小"),
                "key": 'size',
                "minWidth": 90,
                "maxWidth": 120,
                "align": "right",
                "sortable": true,
                render: (h, params) => {
                    return h('span', $A.bytesToSize(params.row.size));
                },
            });
            if (!this.simple) {
                columns.push({
                    "title": this.$L("下载次数"),
                    "key": 'download',
                    "align": "center",
                    "sortable": true,
                    "width": 100,
                });
                columns.push({
                    "title": this.$L("上传者"),
                    "key": 'username',
                    "minWidth": 90,
                    "maxWidth": 130,
                    "sortable": true,
                    render: (h, params) => {
                        return h('UserView', {
                            props: {
                                username: params.row.username
                            }
                        });
                    }
                });
                columns.push({
                    "title": this.$L("上传时间"),
                    "key": 'indate',
                    "width": 160,
                    "sortable": true,
                    render: (h, params) => {
                        return h('span', $A.formatDate("Y-m-d H:i:s", params.row.indate));
                    }
                });
            }
            columns.push({
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
                            props: { content: this.$L('下载'), transfer: true, delay: 600 },
                            style: { position: 'relative' },
                        }, [h('Icon', {
                            props: { type: 'md-arrow-down', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                        }), h('a', {
                            style: { position: 'absolute', top: '0', left: '0', right: '0', bottom: '0', 'zIndex': 1 },
                            attrs: { href: $A.aUrl('project/files/download?fileid=' + params.row.id), target: '_blank' },
                            on: {
                                click: () => {
                                    if (params.row.yetdown) {
                                        return;
                                    }
                                    params.row.yetdown = 1;
                                    this.$set(params.row, 'download', params.row.download + 1);
                                }
                            }
                        })]),
                        h('Tooltip', {
                            props: { content: this.$L('重命名'), transfer: true, delay: 600 }
                        }, [h('Icon', {
                            props: { type: 'md-create', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                            on: {
                                click: () => {
                                    this.renameFile(params.row);
                                }
                            }
                        })]),
                        h('Tooltip', {
                            props: { content: this.$L('复制链接'), transfer: true, delay: 600 }
                        }, [h('Icon', {
                            props: { type: 'md-link', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer', transform: 'rotate(-45deg)' },
                            on: {
                                click: () => {
                                    this.$copyText($A.aUrl('project/files/download?fileid=' + params.row.id)).then(() => {
                                        this.$Message.success(this.$L('复制成功！'));
                                    }, () => {
                                        this.$Message.error(this.$L('复制失败！'));
                                    });
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
                                    this.deleteFile(params.row);
                                }
                            }
                        })]),
                    ]);
                }
            });
            this.columns = columns;
        },

        mounted() {
            if (this.canload) {
                this.loadYet = true;
                this.getLists(true);
            }
            this.uploadList = this.$refs.upload.fileList;
        },

        watch: {
            projectid() {
                if (this.loadYet) {
                    this.getLists(true);
                }
            },
            taskid() {
                if (this.loadYet) {
                    this.getLists(true);
                }
            },
            canload(val) {
                if (val && !this.loadYet) {
                    this.loadYet = true;
                    this.getLists(true);
                }
            },
            uploadList(files) {
                files.forEach((file) => {
                    if (typeof file.username === "undefined") {
                        file.username = $A.getUserName();
                        file.indate = Math.round(new Date().getTime()/1000);
                        this.lists.unshift(file);
                        this.$emit('change', 'up');
                    }
                });
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
                if (this.projectid == 0 && this.taskid == 0) {
                    this.lists = [];
                    this.listTotal = 0;
                    this.noDataText = this.$L("没有相关的文件");
                    return;
                }
                this.loadIng++;
                let whereData = $A.cloneData(this.keys);
                whereData.page = Math.max(this.listPage, 1);
                whereData.pagesize = Math.max($A.runNum(this.listPageSize), 10);
                whereData.projectid = this.projectid;
                whereData.taskid = this.taskid;
                whereData.sorts = this.sorts;
                this.noDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'project/files/lists',
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
                            this.lastPage = res.data.lastPage;
                            this.noDataText = this.$L("没有相关的文件");
                        } else {
                            this.lists = [];
                            this.listTotal = 0;
                            this.lastPage = 0;
                            this.noDataText = res.msg;
                        }
                    }
                });
            },

            renameFile(item) {
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
                            }, this.$L('重命名文件名')),
                            h('Input', {
                                props: {
                                    value: this.renameValue,
                                    autofocus: true,
                                    placeholder: item.name || this.$L('请输入新的文件名称')
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
                            let oldName = item.name;
                            let newName = this.renameValue;
                            if (!$A.rightExists(newName, '.' + item.ext)) {
                                newName += '.' + item.ext;
                            }
                            this.$set(item, 'name', newName);
                            $A.aAjax({
                                url: 'project/files/rename',
                                data: {
                                    fileid: item.id,
                                    name: newName,
                                },
                                error: () => {
                                    this.$Modal.remove();
                                    this.$set(item, 'name', oldName);
                                    alert(this.$L('网络繁忙，请稍后再试！'));
                                },
                                success: (res) => {
                                    this.$Modal.remove();
                                    if (res.ret === 1) {
                                        this.$set(item, 'name', res.data.name);
                                    } else {
                                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                        this.$set(item, 'name', oldName);
                                    }
                                }
                            });
                        } else {
                            this.$Modal.remove();
                        }
                    },
                });
            },

            deleteFile(item) {
                this.$Modal.confirm({
                    title: this.$L('删除文件'),
                    content: this.$L('你确定要删除此文件吗？'),
                    loading: true,
                    onOk: () => {
                        $A.aAjax({
                            url: 'project/files/delete',
                            data: {
                                fileid: item.id,
                            },
                            error: () => {
                                this.$Modal.remove();
                                alert(this.$L('网络繁忙，请稍后再试！'));
                            },
                            success: (res) => {
                                this.$Modal.remove();
                                this.lists.some((temp, index) => {
                                    if (temp.id == item.id) {
                                        this.lists.splice(index, 1);
                                        return true;
                                    }
                                });
                                setTimeout(() => {
                                    if (res.ret === 1) {
                                        this.$Message.success(res.msg);
                                        this.$emit('change', 'delete');
                                    } else {
                                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg });
                                    }
                                }, 350);
                            }
                        });
                    }
                });
            },

            uploadHandleClick() {
                this.$refs.upload.handleClick();
            },

            handleSuccess (res, file) {
                //上传完成
                if (res.ret === 1) {
                    for (let key in res.data) {
                        if (res.data.hasOwnProperty(key)) {
                            file[key] = res.data[key];
                        }
                    }
                    this.$emit('change', 'add');
                } else {
                    this.$Modal.warning({
                        title: this.$L('上传失败'),
                        content: this.$L('文件 % 上传失败，%', file.name, res.msg)
                    });
                    this.$refs.upload.fileList.pop();
                    this.lists.some((item, index) => {
                        if (item.id == res.id) {
                            this.lists.splice(index, 1);
                            return true;
                        }
                    });
                    this.$emit('change', 'error');
                }
                this.uploadList = this.$refs.upload.fileList;
            },

            handleFormatError (file) {
                //上传类型错误
                this.$Modal.warning({
                    title: this.$L('文件格式不正确'),
                    content: this.$L('文件 % 格式不正确，仅支持上传：%', file.name, this.uploadFormat.join(','))
                });
            },

            handleMaxSize (file) {
                //上传大小错误
                this.$Modal.warning({
                    title: this.$L('超出文件大小限制'),
                    content: this.$L('文件 % 太大，不能超过2M。', file.name)
                });
            },
        }
    }
</script>
