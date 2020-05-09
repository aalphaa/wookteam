<template>
    <div class="container main">
        <v-title>Welcome</v-title>

        <div class="content">
            <div class="table-box">
                <div class="table-cell">Hello Welcome!</div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .container.main {
        position: absolute;
        color: #000000;
        top: 0;
        left: 0;
        min-width: 100%;
        min-height: 100%;
        padding: 0;
        margin: 0;
        background: url("../../../statics/images/bg3.jpg") no-repeat center;
        background-size: cover;
        .content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            .table-box {
                text-align: center;
                display: table;
                width: 100%;
                height: 95%;
                .table-cell {
                    display: table-cell;
                    vertical-align: middle;
                    font-size: 50px;
                    margin-top: 50px;
                }
            }
        }
    }
</style>
<script>
    export default { }
</script>
