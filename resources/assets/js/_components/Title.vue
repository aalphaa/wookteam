<template>
    <h1 v-if="false"><slot> </slot></h1>
</template>

<script>
    export default {
        name: 'v-title',
        data() {
            return {
            }
        },
        mounted () {
            this.updateTitle()
        },
        beforeUpdate () {
            this.updateTitle()
        },
        activated() {
            this.updateTitle()
        },
        methods: {
            updateTitle () {
                let slots = this.$slots.default;
                if (typeof slots === 'undefined' || slots.length < 1 || typeof slots[0].text !== 'string') {
                    return;
                }
                let {text} = slots[0];
                let {title} = document;
                if (text !== title) this.setTile(text);
            },
            setTile(title) {
                document.title = title;
                let mobile = navigator.userAgent.toLowerCase();
                if (/iphone|ipad|ipod/.test(mobile)) {
                    let iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    let iframeCallback = function () {
                        setTimeout(function () {
                            iframe.removeEventListener('load', iframeCallback);
                            document.body.removeChild(iframe)
                        }, 0)
                    };
                    iframe.addEventListener('load', iframeCallback);
                    document.body.appendChild(iframe)
                }
            }
        }
    }
</script>
