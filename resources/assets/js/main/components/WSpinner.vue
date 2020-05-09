<template>
    <div class="w-spinner">
        <svg viewBox="25 25 50 50" class="w-circular"><circle cx="50" cy="50" r="20" fill="none" stroke-width="5" stroke-miterlimit="10" class="w-path"></circle></svg>
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
            -webkit-animation: rotate 2s linear infinite;
            animation: rotate 2s linear infinite;
            height: 100%;
            -webkit-transform-origin: center center;
            transform-origin: center center;
            width: 100%;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            margin: auto;
            overflow: hidden;
            .w-path {
                stroke-dasharray: 1,200;
                stroke-dashoffset: 0;
                -webkit-animation: dash 1.5s ease-in-out infinite,color 6s ease-in-out infinite;
                animation: dash 1.5s ease-in-out infinite,color 6s ease-in-out infinite;
                stroke-linecap: round;
            }
        }
        @-webkit-keyframes rotate {
            to {
                -webkit-transform: rotate(1turn);
                transform: rotate(1turn)
            }
        }
        @keyframes rotate {
            to {
                -webkit-transform: rotate(1turn);
                transform: rotate(1turn)
            }
        }
        @-webkit-keyframes dash {
            0% {
                stroke-dasharray: 1,200;
                stroke-dashoffset: 0
            }

            50% {
                stroke-dasharray: 89,200;
                stroke-dashoffset: -35
            }

            to {
                stroke-dasharray: 89,200;
                stroke-dashoffset: -124
            }
        }
        @keyframes dash {
            0% {
                stroke-dasharray: 1,200;
                stroke-dashoffset: 0
            }

            50% {
                stroke-dasharray: 89,200;
                stroke-dashoffset: -35
            }

            to {
                stroke-dasharray: 89,200;
                stroke-dashoffset: -124
            }
        }
        @-webkit-keyframes color {
            0%,to {
                stroke: #d62d20
            }

            40% {
                stroke: #0057e7
            }

            66% {
                stroke: #008744
            }

            80%,90% {
                stroke: #ffa700
            }
        }
        @keyframes color {
            0%,to {
                stroke: #d62d20
            }

            40% {
                stroke: #0057e7
            }

            66% {
                stroke: #008744
            }

            80%,90% {
                stroke: #ffa700
            }
        }
    }
</style>
<script>
    export default {
        name: 'WSpinner',
    }
</script>
