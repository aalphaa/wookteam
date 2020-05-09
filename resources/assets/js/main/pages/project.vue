<template>
    <div class="w-main project">

        <v-title>{{$L('项目')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="project"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <span class="ft hover"><i class="ft icon"></i> {{$L('新建项目')}}</span>
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover"><i class="ft icon"></i> {{$L('收藏的项目')}}</span>
                    <span class="ft hover"><i class="ft icon"></i> {{$L('参与的项目')}}</span>
                    <span class="ft hover"><i class="ft icon"></i> {{$L('我创建的项目')}}</span>
                </div>
            </div>
        </div>

        <w-content></w-content>

    </div>
</template>

<style lang="scss" scoped>
    .project {
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
