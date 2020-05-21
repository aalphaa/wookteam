<template>
    <div class="w-main doc">

        <v-title>{{$L('知识库')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="doc"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <span class="new ft hover"><i class="ft icon"></i> {{$L('新建知识库')}}</span>
                </div>
                <div class="w-nav-flex"></div>
            </div>
        </div>

        <w-content></w-content>

    </div>
</template>

<style lang="scss" scoped>
    .doc {
    }
</style>
<script>
    import WHeader from "../components/WHeader";
    import WContent from "../components/WContent";
    export default {
        components: {WContent, WHeader},
        data () {
            return {
                loadIng: 0,
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
