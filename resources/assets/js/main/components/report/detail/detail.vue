<template>
    <Modal
        v-model="contentShow"
        :title="contentTitle"
        class="report-detail-window"
        width="80%"
        :styles="{top: '35px', paddingBottom: '35px'}"
        footerHide>
        <report-content :content="contentText"></report-content>
    </Modal>
</template>

<script>
    import ReportContent from "../content";
    export default {
        components: {ReportContent},
        data() {
            return {
                reportid: 0,
                reporttitle: '',

                contentShow: false,
                contentTitle: '',
                contentText: '',
            }
        },
        beforeCreate() {
            let doms = document.querySelectorAll('.report-detail-window');
            for (let i = 0; i < doms.length; ++i) {
                if (doms[i].parentNode != null) doms[i].parentNode.removeChild(doms[i]);
            }
        },
        mounted() {
            this.getDetail();
        },
        watch: {
            reportid() {
                this.getDetail();
            }
        },
        methods: {
            getDetail() {
                this.contentShow = true;
                this.contentTitle = this.reporttitle;
                this.contentText = this.$L('详细内容加载中.....');
                $A.aAjax({
                    url: 'report/content?id=' + this.reportid,
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
        }
    }
</script>
