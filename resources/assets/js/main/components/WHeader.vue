<template>
    <div class="w-header">
        <div class="w-header-row">
            <div class="w-header-row-left">
                <ul>
                    <li :class="value==='todo'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('todo')"><i class="ft icon">&#xe89e;</i>待办</a>
                    </li><li :class="value==='project'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('project')"><i class="ft icon">&#xe6b8;</i>项目</a>
                    </li><li :class="value==='doc'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('doc')"><i class="ft icon">&#xe915;</i>知识库</a>
                    </li><li :class="value==='team'?'active':''">
                        <a href="javascript:void(0)" @click="tabPage('team')"><i class="ft icon">&#xe90d;</i>同事</a>
                    </li>
                </ul>
            </div>
            <div class="w-header-row-flex"></div>
            <div class="w-header-row-right">
                <div class="user-info">
                    <span class="username">欢迎您，{{userInfo.username || "尊敬的会员"}}!</span>
                    <ul>
                        <li><span class="ft hover">个人中心</span></li>
                        <li @click="logout"><span class="ft hover">退出登录</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .w-header {
        z-index: 15;
        height: 40px;
        position: fixed;
        left: 0;
        top: 0;
        right: 0;
        font-size: 14px;
        background: #0396f2 linear-gradient(45deg, #0396f2 0%, #0285d7 100%);
        box-shadow: 0 1px 5px 0 rgba(0, 0, 0, 0.25);

        .icon {
            font-size: 16px;
            margin-right: 3px;
        }

        .w-header-row {
            display: flex;
            color: #fff;
            height: 40px;
            position: relative;
            z-index: 10;
            margin: 0 32px;
            .w-header-row-left {
                max-width: 50%;
                white-space: nowrap;
                overflow: hidden;
                overflow-x: auto;
                -webkit-backface-visibility: hidden;
                -webkit-overflow-scrolling: touch;
                -webkit-perspective: 1000;
                li {
                    line-height: 40px;
                    color: #fff;
                    display: inline-block;
                    a {
                        color: #fff;
                        display: block;
                        width: 116px;
                        text-align: center;
                        &:visited {
                            color: #fff;
                        }
                        &:hover {
                            color: #f2f2f2;
                        }
                    }
                }
                li:hover, li.active {
                    background: #0277c0;
                }
            }
            .w-header-row-flex {
                flex: 1;
            }
            .w-header-row-right {
                white-space: nowrap;
                text-align: right;
                line-height: 40px;
                .user-info {
                    display: inline-block;
                    position: relative;
                    margin-right: 6px;
                    cursor: pointer;
                    &:hover {
                        color: #f0f0f0 !important;
                        ul {
                            display: block;
                        }
                    }
                    ul {
                        display: none;
                        position: absolute;
                        background: #fff;
                        border: 1px solid #eee;
                        right: 0;
                        top: 38px;
                        width: 84px;
                        text-align: center;
                        border-radius: 2px;
                        li {
                            height: 36px;
                            line-height: 36px;
                            color: #666;
                            border-bottom: 1px solid #eee;
                            &:last-child {
                                border-bottom: 0;
                            }
                            &:hover {
                                color: #0396f2;
                            }
                        }
                    }
                }
            }
        }
    }
</style>
<script>
    export default {
        name: 'WHeader',
        props: {
            value: {
            },
        },
        data() {
            return {
                userInfo: $A.jsonParse($A.storage("userInfo")),
            }
        },
        mounted() {

        },
        methods: {
            tabPage(path) {
                this.goForward({path: '/' + path});
            },
            logout() {
                this.$Modal.confirm({
                    title: '退出登录',
                    content: '<p>您确定要退出登录吗？</p>',
                    onOk: () => {
                        this.goForward({path: '/'}, true);
                    },
                });
            }
        },
    }
</script>
