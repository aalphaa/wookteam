<template>
    <div class="flow-content">
        <iframe ref="myFlow" class="flow-iframe" :src="url"></iframe>
    </div>
</template>

<style lang="scss" scoped>
    .flow-content {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        .flow-iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 0 0;
            border: 0;
            float: none;
            margin: -1px 0 0;
            max-width: none;
            outline: 0;
            padding: 0;
        }
    }
</style>
<script>

    export default {
        name: "Flow",
        props: {
            value: {
                type: ''
            },
        },
        data() {
            return {
                flow: null,
                url: window.location.origin + '/js/grapheditor/index.html',
            }
        },
        mounted() {
            window.addEventListener('message', this.handleMessage)
            this.flow = this.$refs.myFlow.contentWindow;
        },
        activated() {
            window.addEventListener('message', this.handleMessage)
            this.flow = this.$refs.myFlow.contentWindow;
        },
        methods: {
            handleMessage (event) {
                // 根据上面制定的结构来解析iframe内部发回来的数据
                const data = event.data;
                switch (data.act) {
                    case 'ready':
                        this.flow.postMessage({
                            act: 'setXml',
                            params: {
                                xml: this.value,
                            }
                        }, '*')
                        break

                    case 'change':
                        this.$emit('input', data.params.xml);
                        break
                }
            }
        },
    }
</script>
