<template>
    <div class="dtc-main" :style="myStyle">
        <div class="dtc-body">
            <slot/>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .dtc-main {
        display: flex;
        flex-direction: column;
        width: 100%;
        overflow: hidden;
        position: relative;
        transform: rotateZ(0);

        .dtc-body {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
        }
    }
</style>
<script>
    export default {
        name: 'DrawerTabsContainer',
        data() {
            return {
                calculateHeight: 0,
            }
        },
        mounted() {
            let el = $A(this.$el);
            let eb = el.parents(".ivu-drawer-body");
            this.calculateHeight = eb.outerHeight() - el.offset().top;
            setInterval(() => {
                this.calculateHeight = eb.outerHeight() - el.offset().top;
            }, 1000);
        },
        computed: {
            myStyle() {
                const {calculateHeight} = this;
                return {
                    height: Math.max(0, calculateHeight - 16) + 'px'
                }
            }
        },
    }
</script>
