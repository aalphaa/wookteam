<template>
    <div class="scroller-main" :class="['scroller-' + status, (effect === false ? 'scroller-uneffect' : '')]">
        <div class="scroller-x" ref="scrollerx">
            <slot></slot>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'x-scroller',

        props: ['effect'],

        data() {
            return {
                scrollX: 0,

                status: 'start',

                box: null,
            };
        },

        activated() {
            if (this.scrollX > 0) {
                this.$nextTick(() => {
                    $A(this.$refs.scrollerx).scrollLeft(this.scrollX);
                });
            }
        },

        mounted() {
            this.$nextTick(() => {
                this.box = this.$refs.scrollerx;
                this.box.addEventListener('scroll', () => {
                    this.scrollX = this.box.scrollLeft;
                    if (this.box.scrollLeft > 0) {
                        if (this.box.scrollWidth == this.box.clientWidth + this.box.scrollLeft) {
                            this.status = 'end';
                        } else {
                            this.status = 'center';
                        }
                    } else {
                        if (this.box.scrollWidth > this.box.clientWidth) {
                            this.status = 'start';
                        } else {
                            this.status = '';
                        }
                    }
                }, false);
            });
        }
    }
</script>

<style lang="scss" scoped>
    .scroller-main {
        position: relative;

        .scroller-x {
            display: flex;
            width: 100%;
            flex-wrap: nowrap;
            justify-content: flex-start;
            align-items: center;
            overflow-x: auto;
            overflow-y: hidden;
            position: relative;
            -webkit-overflow-scrolling: touch;

            > div,
            > span {
                display: block;
                flex-grow: 1;
                flex-shrink: 0;
                flex-basis: auto;
            }
        }
    }

    .scroller-center:before,
    .scroller-end:before {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 64px;
        content: "";
        z-index: 1;
        background: linear-gradient(to right, #ffffff, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0));
        pointer-events: none
    }

    .scroller-start:after,
    .scroller-center:after {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 64px;
        content: "";
        z-index: 1;
        background: linear-gradient(to right, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.8), #ffffff);
        pointer-events: none
    }
    
    .scroller-uneffect {
        &:before,
        &:after {
            display: none;
        }
    }
</style>
