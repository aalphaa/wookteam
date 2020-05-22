<template>
    <drawer-tabs-container>
        <div class="report-add">
            <div class="add-header">
                <div class="add-title">
                    <Input v-model="dataDetail.title" placeholder="汇报标题"></Input>
                </div>
                <ButtonGroup>
                    <Button :disabled="id > 0" :type="`${type=='日报'?'primary':'default'}`" @click="type='日报'">日报</Button>
                    <Button :disabled="id > 0" :type="`${type=='周报'?'primary':'default'}`" @click="type='周报'">周报</Button>
                </ButtonGroup>
            </div>
            <t-editor class="add-edit" v-model="dataDetail.content" height="100%"></t-editor>
            <div class="add-input">
                <userid-input v-model="dataDetail.ccuser" placeholder="输入关键词搜索" multiple><span slot="prepend">抄送人</span></userid-input>
            </div>
            <div class="add-footer">
                <Button :loading="loadIng > 0" type="primary" @click="handleSubmit" style="margin-right:6px">保 存</Button>
                <Button v-if="dataDetail.status=='已发送'" :loading="loadIng > 0" type="success" icon="md-checkmark-circle-outline" ghost @click="handleSubmit(true)">已发送</Button>
                <Button v-else :loading="loadIng > 0" @click="handleSubmit(true)">保存并发送</Button>
            </div>
        </div>
    </drawer-tabs-container>
</template>

<style lang="scss">
    .report-add {
        .add-edit {
            .teditor-loadedstyle {
                height: 100%;
            }
        }
    }
</style>
<style lang="scss" scoped>
    .report-add {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 0 24px;
        .add-header {
            display: flex;
            align-items: center;
            margin-top: 22px;
            margin-bottom: 14px;
            .add-title {
                flex: 1;
                padding-right: 36px;
            }
        }
        .add-edit {
            width: 100%;
            flex: 1;
        }
        .add-input,
        .add-footer {
            margin-top: 14px;
        }
    }
</style>
<script>
    import DrawerTabsContainer from "../DrawerTabsContainer";
    import TEditor from "../TEditor";

    /**
     * 新建汇报
     */
    export default {
        name: 'ReportAdd',
        components: {TEditor, DrawerTabsContainer},
        props: {
            id: {
                default: 0
            },
            canload: {
                type: Boolean,
                default: true
            },
        },
        data () {
            return {
                loadYet: false,

                loadIng: 0,

                dataDetail: {
                    title: '',
                    content: '数据加载中.....',
                    ccuser: '',
                    status: '',
                },

                type: '日报'
            }
        },

        mounted() {
            if (this.canload) {
                this.loadYet = true;
                this.getData();
            }
        },

        watch: {
            canload(val) {
                if (val && !this.loadYet) {
                    this.loadYet = true;
                    this.getData();
                }
            },
            type() {
                if (this.loadYet) {
                    this.getData();
                }
            },
            id() {
                if (this.loadYet) {
                    this.dataDetail = {};
                    this.getData();
                }
            },
        },

        methods: {
            getData() {
                this.loadIng++;
                $A.aAjax({
                    url: 'report/template?id=' + this.id + '&type=' + this.type,
                    complete: () => {
                        this.loadIng--;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.dataDetail = res.data;
                        }
                    }
                });
            },

            handleSubmit(send = false) {
                this.loadIng++;
                $A.aAjax({
                    url: 'report/template?act=submit&id=' + this.id + '&type=' + this.type + '&send=' + (send === true ? 1 : 0),
                    method: 'post',
                    data: {
                        D: this.dataDetail
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.dataDetail = res.data;
                            this.$Message.success(res.msg);
                            this.$emit("on-success", res.data);
                        } else {
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                        }
                    }
                });
            }
        }
    }
</script>
