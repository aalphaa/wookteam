<template>
    <div :data-id="info.id">
        <!--文本-->
        <div v-if="info.type==='text'">
            <div v-if="info.self===true" class="list-right">
                <div v-if="info.error" class="item-error" @click="clickError(info.error)">
                    <Icon type="md-alert" />
                </div>
                <div class="item-right">
                    <div class="item-username" @click="clickUser">
                        <em class="item-name"><user-view :username="info.username" placement="left"/></em>
                        <em v-if="info.indate" class="item-date">{{formatCDate(info.indate)}}</em>
                    </div>
                    <div class="item-text">
                        <div class="item-text-view" v-html="textMsg(info.text)"></div>
                    </div>
                </div>
                <img class="item-userimg" @click="clickUser" :src="info.userimg" onerror="this.src=window.location.origin+'/images/other/avatar.png'"/>
            </div>
            <div v-else-if="info.self===false" class="list-item">
                <img class="item-userimg" @click="clickUser" :src="info.userimg" onerror="this.src=window.location.origin+'/images/other/avatar.png'"/>
                <div class="item-left">
                    <div class="item-username" @click="clickUser">
                        <em class="item-name"><user-view :username="info.username" placement="right"/></em>
                        <em v-if="info.__usertag" class="item-tag">{{info.__usertag}}</em>
                        <em v-if="info.indate" class="item-date">{{formatCDate(info.indate)}}</em>
                    </div>
                    <div class="item-text">
                        <div class="item-text-view" v-html="textMsg(info.text)"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--图片-->
        <div v-else-if="info.type==='image'">
            <div v-if="info.self===true" class="list-right">
                <div v-if="info.error" class="item-error" @click="clickError(info.error)">
                    <Icon type="md-alert" />
                </div>
                <div class="item-right">
                    <div class="item-username" @click="clickUser">
                        <em class="item-name"><user-view :username="info.username" placement="left"/></em>
                        <em v-if="info.indate" class="item-date">{{formatCDate(info.indate)}}</em>
                    </div>
                    <a class="item-image" :href="info.url" target="_blank">
                        <img class="item-image-view" :src="info.url"/>
                    </a>
                </div>
                <img class="item-userimg" @click="clickUser" :src="info.userimg" onerror="this.src=window.location.origin+'/images/other/avatar.png'"/>
            </div>
            <div v-else-if="info.self===false" class="list-item">
                <img class="item-userimg" @click="clickUser" :src="info.userimg" onerror="this.src=window.location.origin+'/images/other/avatar.png'"/>
                <div class="item-left">
                    <div class="item-username" @click="clickUser">
                        <em class="item-name"><user-view :username="info.username" placement="right"/></em>
                        <em v-if="info.__usertag" class="item-tag">{{info.__usertag}}</em>
                        <em v-if="info.indate" class="item-date">{{formatCDate(info.indate)}}</em>
                    </div>
                    <a class="item-image" :href="info.url" target="_blank">
                        <img class="item-image-view" :src="info.url"/>
                    </a>
                </div>
            </div>
        </div>

        <!--通知-->
        <div v-else-if="info.type==='notice'">
            <div class="item-notice">{{info.notice}}</div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    /*通用*/
    .list-item, .list-right {
        display: flex;
        width: 100%;
        padding-top: 7px;
        padding-bottom: 7px;
        background-color: #E8EBF2;
        .item-left, .item-right {
            display: flex;
            flex-direction: column;
            max-width: 80%;
            .item-username {
                font-size: 12px;
                padding-top: 1px;
                padding-bottom: 4px;
                display: flex;
                flex-direction: row;
                align-items: center;
                em {
                    display: inline-block;
                    font-style: normal;
                    &.item-name {
                        color: #888888;
                    }
                    &.item-tag {
                        color: #ffffff;
                        background-color: #ff0000;
                        line-height: 16px;
                        padding: 2px 4px;
                        margin-left: 3px;
                        border-radius: 2px;
                        font-size: 12px;
                        transform: scale(0.8);
                        font-weight: 600;
                    }
                    &.item-date {
                        margin-left: 4px;
                        color: #aaaaaa;
                    }
                }
            }
        }
        .item-left {
            align-items: flex-start;
        }
        .item-right {
            align-items: flex-end;
            .item-username {
                text-align: right;
            }
        }
        .item-userimg {
            width: 38px;
            height: 38px;
            margin-left: 8px;
            margin-right: 8px;
            border-radius: 3px;
        }
        .item-error {
            cursor: pointer;
            width: 48px;
            position: relative;
            > i {
                color: #ff0000;
                font-size: 18px;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
        }
    }

    .list-right {
        justify-content: flex-end;
    }

    /*文本*/
    .item-text {
        display: inline-block;
        border-radius: 6px;
        padding: 8px;
        background-color: #ffffff;
        .item-text-view {
            max-width: 520px;
            color: #242424;
            font-size: 14px;
            line-height: 18px;
            word-break: break-all;
        }
    }

    /*图片*/
    .item-image {
        display: inline-block;
        text-decoration: none;
        .item-image-view {
            max-width: 320px;
            max-height: 320px;
            border-radius: 6px;
        }
    }

    /*通知*/
    .item-notice {
        color: #777777;
        font-size: 12px;
        text-align: center;
        padding: 12px 24px;
    }
</style>

<script>

    export default {
        name: 'ChatMessage',
        props: {
            info: {
                type: Object,
                default: {},
            },
        },

        mounted() {

        },

        methods: {
            textMsg(text) {
                return (text + "").replace(/\n/, '<br/>');
            },

            formatCDate(v) {
                let string = '';
                if ($A.runNum(v) > 0) {
                    if ($A.formatDate('Ymd') === $A.formatDate('Ymd', v)) {
                        string = $A.formatDate('H:i', v)
                    } else if ($A.formatDate('Y') === $A.formatDate('Y', v)) {
                        string = $A.formatDate('m-d H:i', v)
                    } else {
                        string = $A.formatDate('Y-m-d H:i', v)
                    }
                }
                return string ? '(' + string + ')' : '';
            },

            clickError(err) {
                this.$Modal.error({
                    title: "错误详情",
                    content: err
                });
            },

            clickUser(e) {
                this.$emit('clickUser', this.info, e);
            },
        }
    }
</script>
