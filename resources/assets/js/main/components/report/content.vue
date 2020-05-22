<template>
    <iframe class="report-content" :style="{height: contentHeight + 'px'}"></iframe>
</template>

<style lang="scss" scoped>
    .report-content {
        background: 0 0;
        border: 0;
        float: none;
        margin: 6px 0;
        max-width: none;
        outline: 0;
        padding: 0;
        position: static;
        width: 100%;
    }
</style>
<script>
    /**
     * 预览内容
     */
    export default {
        name: 'ReportContent',
        props: {
            content: {
                default: ''
            },
        },
        data() {
            return {
                contentHeight: 50,
            }
        },
        mounted() {
            this.setContent(this.content);
        },
        watch: {
            content(val) {
                this.setContent(val);
            }
        },
        methods: {
            setContent(val) {
                let $d = this.$el.contentWindow.document;
                $A("body", $d).html('<link type="text/css" rel="stylesheet" href="' + window.location.origin + '/js/build/skins/ui/oxide/content.min.css"><style>html,body{padding:0;margin:0}</style><div id="content">' + val + '</div>');
                this.$nextTick(() => {
                    this.contentHeight = $d.getElementById("content").scrollHeight;
                });
            }
        }
    }
</script>
