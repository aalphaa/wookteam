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
        background-image: url("../../../statics/images/bg1.jpg");

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
