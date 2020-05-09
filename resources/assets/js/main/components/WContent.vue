<template>
    <div class="w-content">
        <slot/>
    </div>
</template>

<style lang="scss" scoped>
    .w-content {
        position: absolute;
        top: 72px;
        left: 0;
        right: 0;
        bottom: 0;
        overflow: auto;
        background: url("../../../statics/images/bg1.jpg") no-repeat center;
        background-size: cover;

        .w-container {
            min-height: 500px;
        }
    }
</style>
<script>
    export default {
        name: 'WContent',
    }
</script>
