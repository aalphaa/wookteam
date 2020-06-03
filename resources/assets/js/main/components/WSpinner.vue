<template>
    <div class="w-spinner">
        <w-loading class="w-circular"></w-loading>
    </div>
</template>

<style lang="scss" scoped>
    .w-spinner {
        display: none;
        position: fixed;
        z-index: 9999;
        bottom: 20px;
        right: 20px;
        margin: 0 auto;
        width: 30px;
        height: 30px;
        .w-circular {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 100%;
        }
    }
</style>
<script>
    export default {
        name: 'WSpinner',
    }
</script>
