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
                clientHeight: 0,
                clientWidth: 0,
            }
        },
        mounted() {
            Spreadsheet.locale('zh-cn', zhCN);
            //
            this.sheet = new Spreadsheet(this.$refs.xspreadsheet, {
                view: {
                    height: () => {
                        try {
                            return this.clientHeight = this.$refs.xspreadsheet.clientHeight;
                        }catch (e) {
                            return this.clientHeight;
                        }
                    },
                    width: () => {
                        try {
                            return this.clientWidth = this.$refs.xspreadsheet.clientWidth;
                        }catch (e) {
                            return this.clientWidth;
                        }
                    },
                },
            }).loadData(this.value).change(data => {
                this.$emit('input', data);
            });
            //
            this.sheet.validate()
        },
    }
</script>
