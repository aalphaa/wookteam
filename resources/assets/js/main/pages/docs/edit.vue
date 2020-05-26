<template>
    <div class="w-main docs-edit">

        <v-title>{{$L('文档编辑')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <div class="edit-box">
            <div class="edit-header">
                <div class="header-menu active" @click="handleClick('back')"><Icon type="md-arrow-back" /></div>
                <div class="header-menu" @click="handleClick('menu')"><Icon type="md-menu" /></div>
                <div class="header-menu"><Icon type="md-share" /></div>
                <div class="header-menu"><Icon type="md-eye" /></div>
                <div class="header-menu"><Icon type="md-time" /></div>
                <div class="header-title">{{docDetail.title}}</div>
                <div v-if="docDetail.type=='mind'" class="header-hint">选中节点，按enter键添加子节点，tab键添加同级节点</div>
                <Button :disabled="disabledBtn || loadIng > 0" class="header-button" size="small" type="primary" @click="handleClick('save')">保存</Button>
            </div>
            <div class="docs-body">
                <t-editor v-if="docDetail.type=='document'" class="body-text" v-model="docContent.content" height="100%"></t-editor>
                <minder v-else-if="docDetail.type=='mind'" class="body-mind" v-model="docContent"></minder>
                <sheet v-else-if="docDetail.type=='sheet'" class="body-sheet" v-model="docContent.content"></sheet>
                <flow v-else-if="docDetail.type=='flow'" class="body-flow" v-model="docContent.content"></flow>
            </div>
        </div>

    </div>
</template>


<style lang="scss">
    .docs-edit {
        .body-text {
            .teditor-loadedstyle {
                .tox-tinymce {
                    border: 0;
                    border-radius: 0;
                }
                .tox-mbtn {
                    height: 28px;
                }
                .tox-menubar,
                .tox-toolbar-overlord {
                    padding: 0 12%;
                    background: #f9f9f9;
                }
                .tox-toolbar__overflow,
                .tox-toolbar__primary {
                    background: none !important;
                    border-top: 1px solid #eaeaea !important;
                }
                .tox-toolbar-overlord {
                    border-bottom: 1px solid #E9E9E9 !important;
                }
                .tox-toolbar__group:not(:last-of-type) {
                    border-right: 1px solid #eaeaea !important;
                }
                .tox-sidebar-wrap {
                    margin: 22px 12%;
                    border: 1px solid #e8e8e8;
                    border-radius: 2px;
                    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.08);
                    .tox-edit-area {
                        border-top: 0;
                    }
                }
                .tox-statusbar {
                    border-top: 1px solid #E9E9E9;
                    .tox-statusbar__resize-handle {
                        display: none;
                    }
                }
            }
        }
        .body-sheet {
            box-sizing: content-box;
            * {
                box-sizing: content-box;
            }
        }
    }
</style>
<style lang="scss" scoped>
    .docs-edit {
        .edit-box {
            display: flex;
            flex-direction: column;
            position: absolute;
            width: 100%;
            height: 100%;
            .edit-header {
                display: flex;
                flex-direction: row;
                align-items: center;
                width: 100%;
                height: 38px;
                background-color: #ffffff;
                box-shadow: 0 1px 5px 0 rgba(0, 0, 0, 0.1);
                position: relative;
                z-index: 9;
                .header-menu {
                    width: 50px;
                    height: 100%;
                    text-align: center;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 3px;
                    cursor: pointer;
                    color: #777777;
                    position: relative;
                    .ivu-icon {
                        font-size: 16px;
                    }
                    &:hover,
                    &.active {
                        color: #fff;
                        background: #059DFD;
                    }
                }
                .header-title {
                    flex: 1;
                    color: #333333;
                    border-left: 1px solid #ddd;
                    margin-left: 5px;
                    padding-left: 24px;
                    padding-right: 24px;
                    font-size: 16px;
                    white-space: nowrap;
                }
                .header-hint {
                    padding-right: 22px;
                    font-size: 12px;
                    color: #666;
                    white-space: nowrap;
                }
                .header-button {
                    font-size: 12px;
                    margin-right: 12px;
                }
            }
            .docs-body {
                flex: 1;
                width: 100%;
                position: relative;
                .body-text {
                    display: flex;
                    width: 100%;
                    height: 100%;
                    .teditor-loadedstyle {
                        height: 100%;
                    }
                }
            }
        }
    }
</style>
<script>
    import Vue from 'vue'
    import minder from '../../components/docs/minder'
    import TEditor from "../../components/TEditor";
    import Sheet from "../../components/docs/sheet/index";
    import Flow from "../../components/docs/flow/index";

    Vue.use(minder)

    export default {
        components: {Flow, Sheet, TEditor},
        data () {
            return {
                loadIng: 0,

                sid: 0,

                docDetail: { },
                docContent: { },
                bakContent: null,
            }
        },
        mounted() {

        },
        activated() {
            this.sid = this.$route.params.sid;
            if (typeof this.$route.params.other === "object") {
                this.$set(this.docDetail, 'title', $A.getObject(this.$route.params.other, 'title'))
            }
        },
        deactivated() {
            if ($A.getToken() === false) {
                this.sid = 0;
            }
        },
        watch: {
            sid(val) {
                if ($A.runNum(val) <= 0) {
                    this.goBack();
                    return;
                }
                this.docDetail = { };
                this.docContent = { };
                this.bakContent = null;
                this.getDetail();
            }
        },
        computed: {
            disabledBtn() {
                let tmpContent = $A.jsonStringify(this.docContent);
                return this.bakContent == tmpContent;
            }
        },
        methods: {
            getDetail() {
                this.loadIng++;
                $A.aAjax({
                    url: 'docs/section/content',
                    data: {
                        id: this.sid,
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.goBack();
                        alert(this.$L('网络繁忙，请稍后再试！'));
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.docDetail = res.data;
                            this.docContent = $A.jsonParse(res.data.content);
                            this.bakContent = $A.jsonStringify(this.docContent);
                        } else {
                            this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                            this.goBack();
                        }
                    }
                });
            },

            exportMindData(json) {
                this.docContent = json;
            },

            handleClick(act) {
                switch (act) {
                    case "back":
                    case "save":
                        let tmpContent = $A.jsonStringify(this.docContent);
                        if (this.bakContent != tmpContent) {
                            this.bakContent = tmpContent;
                            $A.aAjax({
                                url: 'docs/section/save?id=' + this.sid,
                                method: 'post',
                                data: {
                                    D: Object.assign(this.docDetail, {content: tmpContent})
                                },
                                error: () => {
                                    alert(this.$L('网络繁忙，保存失败！'));
                                },
                                success: (res) => {
                                    if (res.ret === 1) {
                                        this.$Message.success(res.msg);
                                    } else {
                                        this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                    }
                                }
                            });
                        }
                        if (act == 'back') {
                            this.goBack();
                        }
                        break;

                    case "menu":
                        break;

                }
            }
        },
    }
</script>
