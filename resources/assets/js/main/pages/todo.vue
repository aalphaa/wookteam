<template>
    <div class="w-main todo">

        <v-title>{{$L('待办')}}-{{$L('轻量级的团队在线协作')}}</v-title>

        <w-header value="todo"></w-header>

        <div class="w-nav">
            <div class="nav-row">
                <div class="w-nav-left">
                    <i class="ft icon">&#xE787;</i> {{$L('我的待办')}}
                </div>
                <div class="w-nav-flex"></div>
                <div class="w-nav-right">
                    <span class="ft hover"><i class="ft icon">&#xE706;</i> {{$L('待办日程')}}</span>
                    <span class="ft hover"><i class="ft icon">&#xE73D;</i> {{$L('已完成的任务')}}</span>
                    <span class="ft hover"><i class="ft icon">&#xE748;</i> {{$L('我关注的任务')}}</span>
                    <span class="ft hover"><i class="ft icon">&#xE743;</i> {{$L('周报/日报')}}</span>
                </div>
            </div>
        </div>

        <w-content>
            <div class="todo-main">
                <ul>
                    <li>
                        <div class="todo-card">
                            <div class="todo-card-head p1">
                                <i class="ft icon flag">&#xE753;</i>
                                <div class="todo-card-title">{{$L('重要且紧急')}}</div>
                                <i class="ft icon close">&#xE740;</i>
                            </div>
                            <div class="todo-card-content">
                                <div class="empty">{{$L('恭喜你！已完成了所有待办')}}</div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="todo-card">
                            <div class="todo-card-head p2">
                                <i class="ft icon flag">&#xE753;</i>
                                <div class="todo-card-title">{{$L('重要不紧急')}}</div>
                                <i class="ft icon close">&#xE740;</i>
                            </div>
                            <div class="todo-card-content">
                                <div class="empty">{{$L('恭喜你！已完成了所有待办')}}</div>
                            </div>
                        </div>
                    </li>
                </ul>
                <ul>
                    <li>
                        <div class="todo-card">
                            <div class="todo-card-head p3">
                                <i class="ft icon flag">&#xE753;</i>
                                <div class="todo-card-title">{{$L('紧急不重要')}}</div>
                                <i class="ft icon close">&#xE740;</i>
                            </div>
                            <div class="todo-card-content">
                                <div class="empty">{{$L('恭喜你！已完成了所有待办')}}</div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="todo-card">
                            <div class="todo-card-head p4">
                                <i class="ft icon flag">&#xE753;</i>
                                <div class="todo-card-title">{{$L('不重要不紧急')}}</div>
                                <i class="ft icon close">&#xE740;</i>
                            </div>
                            <div class="todo-card-content">
                                <div class="empty">{{$L('恭喜你！已完成了所有待办')}}</div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </w-content>

    </div>
</template>

<style lang="scss" scoped>
    .todo {
        .todo-main {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            min-height: 500px;
            padding: 5px;
            ul {
                flex: 1;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                width: 100%;
                li {
                    flex: 1;
                    height: 100%;
                    position: relative;
                    .todo-card {
                        position: absolute;
                        top: 10px;
                        left: 10px;
                        right: 10px;
                        bottom: 10px;
                        display: flex;
                        flex-direction: column;
                        .todo-card-head {
                            display: flex;
                            align-items: center;
                            padding: 8px 10px;
                            border-radius: 4px 4px 0 0;
                            color: #ffffff;
                            .ft.icon {
                                transform: scale(1);
                            }
                            .flag {
                                font-weight: bold;
                                font-size: 14px;
                                margin-right: 5px;
                            }
                            .close {
                                font-size: 18px;
                                font-weight: normal;
                            }
                            .todo-card-title {
                                flex: 1;
                                font-weight: bold;
                            }
                            &.p1 {
                                background: rgba(248, 14, 21, 0.6);
                            }
                            &.p2 {
                                background: rgba(236, 196, 2, 0.5);
                            }
                            &.p3 {
                                background: rgba(0, 159, 227, 0.7);
                            }
                            &.p4 {
                                background: rgba(121, 170, 28, 0.7);
                            }
                        }
                        .todo-card-content {
                            flex: 1;
                            background-color: #f5f6f7;
                            border-radius: 0 0 4px 4px;
                            .empty {
                                margin: 20px auto;
                                text-align: center;
                                color: #666;
                            }
                        }
                    }
                }
            }
        }
    }
</style>
<script>
    import WHeader from "../components/WHeader";
    import WContent from "../components/WContent";
    export default {
        components: {WContent, WHeader},
        data () {
            return {
                userInfo: {},
            }
        },
        mounted() {
            this.userInfo = $A.getUserInfo((res) => {
                this.userInfo = res;
            });
        },
        computed: {

        },
        watch: {

        },
        methods: {

        },
    }
</script>
