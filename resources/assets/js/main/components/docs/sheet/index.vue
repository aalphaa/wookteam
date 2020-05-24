<template>
    <div ref="xspreadsheet" class="xspreadsheet"></div>
</template>

<style lang="scss" scoped>
    .xspreadsheet {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
    }
</style>
<script>
    import Spreadsheet from 'x-data-spreadsheet';
    import zhCN from 'x-data-spreadsheet/dist/locale/zh-cn';

    export default {
        name: "Sheet",
        props: {
            value: {
                type: Object,
                default: function () {
                    return {}
                }
            },
        },
        data() {
            return {
                sheet: null,
            }
        },
        mounted() {
            Spreadsheet.locale('zh-cn', zhCN);
            //
            this.sheet = new Spreadsheet(this.$refs.xspreadsheet, {
                view: {
                    height: () => this.$refs.xspreadsheet.clientHeight,
                    width: () => this.$refs.xspreadsheet.clientWidth,
                },
            }).loadData(this.value).change(data => {
                this.$emit('input', data);
            });
            //
            this.sheet.validate()
        },
    }
</script>
