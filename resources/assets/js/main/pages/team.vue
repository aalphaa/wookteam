<template>
    <div class="w-main team">

        <v-title>团队-轻量级的团队在线协作</v-title>

        <w-header value="team"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <i class="ft icon"></i>同事列表
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover"><i class="ft icon"></i>添加同事</span>
                </div>
            </div>
        </div>

        <w-content></w-content>

    </div>
</template>

<style lang="scss" scoped>
    .team {
    }
</style>
<script>
    import WHeader from "../components/WHeader";
    import WContent from "../components/WContent";
    export default {
        components: {WContent, WHeader},
        data () {
            return {

            }
        },
        mounted() {

        },
        computed: {

        },
        watch: {

        },
        methods: {

        },
    }
</script>
