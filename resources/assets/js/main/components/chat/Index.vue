<template>
    <div class="chat-index">

        <!--左边选项-->
        <ul class="chat-menu">
            <li class="self">
                <img :src="userInfo.userimg">
            </li>
            <li :class="{active:chatTap=='dialog'}" @click="chatTap='dialog'">
                <Icon type="md-text" />
                <em v-if="unreadTotal > 0" class="chat-num">{{unreadTotal}}</em>
            </li>
            <li :class="{active:chatTap=='team'}" @click="chatTap='team'">
                <Icon type="md-person" />
            </li>
        </ul>

        <!--对话列表-->
        <ul class="chat-user" :style="{display:chatTap=='dialog'?'flex':'none'}">
            <li class="sreach">
                <Input placeholder="搜索" prefix="ios-search" v-model="dialogSearch"/>
            </li>
            <li class="lists">
                <ul>
                    <li v-for="(dialog, index) in dialogListsS"
                        :key="index"
                        :class="{active:dialog.username==dialogTarget.username}"
                        @click="openDialog(dialog)">
                        <img :src="dialog.userimg">
                        <div class="user-msg-box">
                            <div class="user-msg-title">
                                <span><user-view :username="dialog.username" placement="right" @on-result="(n)=>{dialog['nickname']=n}"/></span>
                                <em>{{formatCDate(dialog.lastdate)}}</em>
                            </div>
                            <div class="user-msg-text">{{dialog.lasttext}}</div>
                        </div>
                        <em v-if="dialog.unread > 0" class="user-msg-num">{{dialog.unread}}</em>
                    </li>
                    <li v-if="dialogNoDataText==$L('数据加载中.....')" class="chat-none"><w-loading/></li>
                    <li v-else-if="dialogLists.length == 0" class="chat-none">{{dialogNoDataText}}</li>
                </ul>
            </li>
        </ul>

        <!--联系人列表-->
        <ul class="chat-team" :style="{display:chatTap=='team'?'flex':'none'}">
            <li class="sreach">
                <Input placeholder="搜索" prefix="ios-search" v-model="teamSearch"/>
            </li>
            <li class="lists">
                <ul>
                    <li v-for="(lists, key) in teamLists">
                        <div class="team-label">{{key}}</div>
                        <ul>
                            <li v-for="(item, index) in teamListsS(lists)" :key="index" @click="openDialog(item, true)">
                                <img :src="item.userimg">
                                <div class="team-username"><user-view :username="item.username" placement="right" @on-result="(n)=>{item['nickname']=n}"/></div>
                            </li>
                        </ul>
                    </li>
                    <li v-if="teamNoDataText==$L('数据加载中.....')" class="chat-none"><w-loading/></li>
                    <li v-else-if="Object.keys(teamLists).length == 0" class="chat-none">{{teamNoDataText}}</li>
                    <li v-if="teamHasMorePages" class="chat-more" @click="getTeamLists(true)">加载更多...</li>
                </ul>
            </li>
        </ul>

        <!--对话窗口-->
        <div class="chat-message" :style="{display:(chatTap=='dialog'&&dialogTarget.username)?'block':'none'}">
            <div class="manage-title">
                <user-view :username="dialogTarget.username"/>
                <Dropdown class="manage-title-right" placement="bottom-end" trigger="click" @on-click="dialogDropdown" transfer>
                    <Icon type="ios-more"/>
                    <DropdownMenu slot="list">
                        <DropdownItem name="delete">删除对话</DropdownItem>
                        <DropdownItem name="clear">清除聊天记录</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </div>
            <ScrollerY ref="manageLists" class="manage-lists" @on-scroll="messageListsScroll">
                <div ref="manageBody" class="manage-body">
                    <div v-if="messageHasMorePages" class="manage-more" @click="getDialogMessage(true)">加载更多...</div>
                    <div v-if="messageNoDataText==$L('数据加载中.....')" class="manage-more"><w-loading/></div>
                    <div v-else-if="messageNoDataText" class="manage-more">{{messageNoDataText}}</div>
                    <chat-message v-for="(info, index) in messageLists" :key="index" :info="info"></chat-message>
                </div>
                <div class="manage-lists-message-new" v-if="messageNew > 0" @click="messageBottomGo(true)">有{{messageNew}}条新消息</div>
            </ScrollerY>
            <div class="manage-send" @click="clickDialog(dialogTarget.username)">
                <textarea ref="textarea" class="manage-input" v-model="messageText" placeholder="请输入要发送的消息" @keydown="messageSend($event)"></textarea>
            </div>
            <div class="manage-quick">
                <emoji-picker @emoji="messageInsertText" :search="messageEmojiSearch">
                    <div slot="emoji-invoker" slot-scope="{ events: { click: clickEvent } }" @click.stop="clickEvent">
                        <Icon class="quick-item" type="ios-happy-outline"  />
                    </div>
                    <div slot="emoji-picker" slot-scope="{ emojis, insert, display }">
                        <div class="emoji-box">
                            <Input class="emoji-input" placeholder="搜索" v-model="messageEmojiSearch" prefix="ios-search"/>
                            <div>
                                <div v-for="(emojiGroup, category) in emojis" :key="category">
                                    <h5>{{ category }}</h5>
                                    <div class="emojis">
                                        <span v-for="(emoji, emojiName) in emojiGroup" :key="emojiName" @click="insert(emoji)" :title="emojiName">{{ emoji }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </emoji-picker>
                <Icon class="quick-item" type="ios-photos-outline" @click="$refs.messageUpload.handleClick()"/>
                <img-upload ref="messageUpload" class="message-upload" type="callback" @on-callback="messageInsertImage" num="3" :otherParams="{from:'chat'}"></img-upload>
            </div>
        </div>

    </div>
</template>

<style lang="scss">
    .chat-notice-box {
        display: flex;
        align-items: flex-start;
        .chat-notice-userimg {
            width: 42px;
            height: 42px;
            border-radius: 4px;
        }
        .ivu-notice-with-desc {
            flex: 1;
            padding: 0 12px;
        }
        .ivu-notice-desc {
            word-break:break-all;
            line-height: 1.3;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            overflow:hidden;
            -webkit-box-orient: vertical;
        }
    }
</style>
<style lang="scss" scoped>
    .chat-index {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        .chat-none {
            height: auto;
            color: #666666;
            padding: 22px 8px;
            text-align: center;
            justify-content: center;
            margin: 0 !important;
            &:before {
                display: none;
            }
        }
        .chat-more {
            color: #666666;
            padding: 18px 0;
            text-align: center;
            cursor: pointer;
            margin: 0 !important;
            &:hover {
                color: #444444;
            }
        }
        .chat-menu {
            background-color: rgba(28, 29, 31, 0.92);
            width: 68px;
            height: 100%;
            padding-top: 20px;
            li {
                position: relative;
                padding: 12px 0;
                text-align: center;
                font-size: 28px;
                color: #919193;
                background-color: transparent;
                cursor: pointer;
                &.self {
                    img {
                        width: 36px;
                        height: 36px;
                        border-radius: 3px;
                    }
                }
                &.active {
                    color: #ffffff;
                    background-color: rgba(255, 255, 255, 0.06);
                }
                .chat-num {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    height: auto;
                    line-height: normal;
                    color: #ffffff;
                    background-color: #ff0000;
                    text-align: center;
                    border-radius: 10px;
                    padding: 1px 5px;
                    font-size: 12px;
                    transform: scale(0.9) translate(5px, -20px);
                }
            }
        }
        .chat-user {
            display: flex;
            flex-direction: column;
            width: 248px;
            height: 100%;
            background-color: #ffffff;
            border-right: 1px solid #ededed;
            > li {
                position: relative;
                &.sreach {
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    height: 62px;
                    margin: 0;
                    padding: 0 12px;
                    position: relative;
                    cursor: pointer;

                    &:before {
                        content: "";
                        position: absolute;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        height: 1px;
                        background-color: rgba(0, 0, 0, 0.06);
                    }
                }
                &.lists {
                    flex: 1;
                    overflow: auto;
                    transform: translateZ(0);
                    > ul {
                        > li {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            height: 70px;
                            padding: 0 12px;
                            position: relative;
                            cursor: pointer;
                            &:before {
                                content: "";
                                position: absolute;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                height: 1px;
                                background-color: rgba(0, 0, 0, 0.06);
                            }
                            &.active {
                                &:before {
                                    top: 0;
                                    height: 100%;
                                }
                            }
                            img {
                                width: 42px;
                                height: 42px;
                                border-radius: 4px;
                            }
                            .user-msg-box {
                                flex: 1;
                                display: flex;
                                flex-direction: column;
                                padding-left: 12px;
                                .user-msg-title {
                                    display: flex;
                                    flex-direction: row;
                                    align-items: center;
                                    justify-content: space-between;
                                    line-height: 24px;
                                    span {
                                        flex: 1;
                                        max-width: 130px;
                                        color: #333333;
                                        font-size: 14px;
                                        white-space: nowrap;
                                        overflow: hidden;
                                        text-overflow: ellipsis;
                                    }
                                    em {
                                        color: #999999;
                                        font-size: 12px;
                                    }
                                }
                                .user-msg-text {
                                    max-width: 170px;
                                    color: #999999;
                                    font-size: 12px;
                                    line-height: 24px;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }
                            }
                        }
                    }
                }
                .user-msg-num {
                    position: absolute;
                    top: 6px;
                    left: 44px;
                    height: auto;
                    line-height: normal;
                    color: #ffffff;
                    background-color: #ff0000;
                    text-align: center;
                    border-radius: 10px;
                    padding: 1px 5px;
                    font-size: 12px;
                    transform: scale(0.9);
                    border: 1px solid #ffffff;
                }
            }
        }
        .chat-team {
            display: flex;
            flex-direction: column;
            width: 248px;
            height: 100%;
            background-color: #ffffff;
            border-right: 1px solid #ededed;
            > li {
                position: relative;
                &.sreach {
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    height: 62px;
                    margin: 0;
                    padding: 0 12px;
                    position: relative;
                    cursor: pointer;

                    &:before {
                        content: "";
                        position: absolute;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        height: 1px;
                        background-color: rgba(0, 0, 0, 0.06);
                    }
                }
                &.lists {
                    flex: 1;
                    overflow: auto;
                    transform: translateZ(0);
                    > ul {
                        > li {
                            margin-left: 24px;
                            position: relative;
                            .team-label {
                                padding-left: 4px;
                                margin-top: 6px;
                                margin-bottom: 6px;
                                height: 34px;
                                line-height: 34px;
                                border-bottom: 1px solid #efefef;
                            }
                            > ul {
                                > li {
                                    display: flex;
                                    flex-direction: row;
                                    align-items: center;
                                    height: 52px;
                                    cursor: pointer;
                                    img {
                                        width: 30px;
                                        height: 30px;
                                        border-radius: 3px;
                                    }
                                    .team-username {
                                        padding: 0 12px;
                                        font-size: 14px;
                                        white-space: nowrap;
                                        overflow: hidden;
                                        text-overflow: ellipsis;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        .chat-message {
            flex: 1;
            height: 100%;
            background-color: #F3F3F3;
            position: relative;
            .manage-title {
                position: absolute;
                top: 0;
                left: 0;
                z-index: 3;
                width: 100%;
                height: 62px;
                padding: 0 20px;
                line-height: 62px;
                font-size: 16px;
                font-weight: 500;
                text-align: left;
                background: #ffffff;
                border-bottom: 1px solid #ededed;
                .manage-title-right {
                    position: absolute;
                    top: 0;
                    right: 0;
                    z-index: 9;
                    width: 62px;
                    height: 62px;
                    line-height: 62px;
                    text-align: center;
                    font-size: 22px;
                    color: #242424;
                }
            }
            .manage-lists {
                position: absolute;
                left: 0;
                top: 62px;
                z-index: 1;
                bottom: 120px;
                width: 100%;
                overflow: auto;
                padding: 8px 0;
                background-color: #E8EBF2;
                .manage-more {
                    color: #666666;
                    padding: 8px 0;
                    text-align: center;
                    cursor: pointer;
                    &:hover {
                        color: #444444;
                    }
                }
                .manage-lists-message-new {
                    position: fixed;
                    bottom: 130px;
                    right: 20px;
                    color: #ffffff;
                    background-color: rgba(0, 0, 0, 0.6);
                    padding: 6px 12px;
                    border-radius: 16px;
                    font-size: 12px;
                    cursor: pointer;
                }
            }
            .manage-send {
                position: absolute;
                left: 0;
                bottom: 0;
                z-index: 2;
                display: flex;
                width: 100%;
                height: 120px;
                background-color: #ffffff;
                border-top: 1px solid #e4e4e4;
                .manage-input,.manage-input:focus {
                    flex: 1;
                    -webkit-appearance: none;
                    font-size: 14px;
                    box-sizing: border-box;
                    padding: 0;
                    margin: 38px 10px 6px;
                    border: 0;
                    line-height: 20px;
                    box-shadow: none;
                    resize:none;
                    outline: 0;
                }
                .manage-join,
                .manage-spin {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: #ffffff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            }
            .manage-quick {
                position: absolute;
                z-index: 2;
                left: 0;
                right: 0;
                bottom: 79px;
                padding: 8px;
                display: flex;
                align-items: center;
                .quick-item {
                    color: #444444;
                    font-size: 24px;
                    margin-right: 12px;
                }
                .emoji-box {
                    position: absolute;
                    left: 0;
                    bottom: 40px;
                    max-height: 320px;
                    width: 100%;
                    overflow: auto;
                    background-color: #ffffff;
                    padding: 12px;
                    border-bottom: 1px solid #efefef;
                    .emoji-input {
                        margin: 6px 0;
                    }
                    h5 {
                        padding: 0;
                        margin: 8px 0 0 0;
                        color: #b1b1b1;
                        text-transform: uppercase;
                        font-size: 14px;
                        cursor: default;
                        font-weight: normal;
                    }
                    .emojis {
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: space-between;
                        &:after {
                            content: "";
                            flex: auto;
                        }
                        span {
                            padding: 2px 4px;
                            cursor: pointer;
                            font-size: 22px;
                            &:hover {
                                background: #ececec;
                                cursor: pointer;
                            }
                        }
                    }
                }
                .message-upload {
                    display: none;
                    width: 0;
                    height: 0;
                    overflow: hidden;
                }
            }
            @media screen and (max-width: 768px) {
                .manage-lists {
                    bottom: 96px;
                    .manage-lists-message-new {
                        bottom: 106px;
                    }
                }
                .manage-send {
                    height: 96px;
                }
                .manage-quick {
                    bottom: 54px;
                    .quick-item {
                        font-size: 24px;
                        margin-right: 8px;
                    }
                }
            }
        }
    }
</style>
<script>
    import EmojiPicker from 'vue-emoji-picker'
    import DrawerTabsContainer from "../DrawerTabsContainer";
    import ScrollerY from "../../../_components/ScrollerY";
    import ChatMessage from "./message";
    import ImgUpload from "../ImgUpload";

    export default {
        name: 'ChatIndex',
        components: {ImgUpload, ChatMessage, EmojiPicker, ScrollerY, DrawerTabsContainer},
        props: {
            value: {
                default: 0
            },
            openWindow: {
                type: Boolean,
                default: false
            },
        },
        data () {
            return {
                loadIng: 0,

                userInfo: {},

                chatTap: 'dialog',

                dialogSearch: '',
                dialogTarget: {},
                dialogLists: [],
                dialogNoDataText: '',

                teamSearch: '',
                teamReady: false,
                teamLists: {},
                teamNoDataText: '',
                teamCurrentPage: 1,
                teamHasMorePages: false,

                autoBottom: true,
                messageNew: 0,
                messageText: '',
                messageLists: [],
                messageNoDataText: '',
                messageEmojiSearch: '',
                messageCurrentPage: 1,
                messageHasMorePages: false,

                unreadTotal: 0,
            }
        },

        created() {
            this.dialogNoDataText = this.$L("数据加载中.....");
            this.teamNoDataText = this.$L("数据加载中.....");
            this.messageNoDataText = this.$L("数据加载中.....");
        },

        mounted() {
            let resCall = () => {
                if ($A.getToken() === false) {
                    return;
                }
                $A.WS.sendTo('unread', (res) => {
                    if (res.status === 1) {
                        this.unreadTotal = $A.runNum(res.message);
                    } else {
                        this.unreadTotal = 0;
                    }
                });
                this.getDialogLists();
                this.messageBottomAuto();
            };
            this.userInfo = $A.getUserInfo((res, isLogin) => {
                if (this.userInfo.id != res.id) {
                    this.userInfo = res;
                    resCall();
                }
            }, false);
            resCall();
            //
            $A.WS.setOnMsgListener("chat/index", (msgDetail) => {
                if (msgDetail.sender == $A.getUserName()) {
                    return;
                }
                if (msgDetail.messageType != 'send') {
                    return;
                }
                //
                let data = $A.jsonParse(msgDetail.content);
                if (['taskA'].indexOf(data.type) !== -1) {
                    return;
                }
                let lasttext;
                switch (data.type) {
                    case 'text':
                        lasttext = data.text;
                        break;
                    case 'image':
                        lasttext = '[图片]';
                        break;
                    case 'taskB':
                        lasttext = data.text + " [来自关注任务]";
                        break;
                    case 'report':
                        lasttext = data.text + " [来自工作报告]";
                        break;
                    default:
                        lasttext = '[未知类型]';
                        break;
                }
                let plusUnread = msgDetail.sender != this.dialogTarget.username || !this.openWindow;
                this.addDialog({
                    username: data.username,
                    userimg: data.userimg,
                    lasttext: lasttext,
                    lastdate: data.indate
                }, plusUnread);
                if (msgDetail.sender == this.dialogTarget.username) {
                    this.addMessageData(data, true);
                }
                if (!plusUnread) {
                    $A.WS.sendTo('read', data.username);
                }
                if (!this.openWindow) {
                    this.$Notice.close('chat-notice');
                    this.$Notice.open({
                        name: 'chat-notice',
                        duration: 0,
                        render: h => {
                            return h('div', {
                                class: 'chat-notice-box',
                                on: {
                                    click: () => {
                                        this.$Notice.close('chat-notice');
                                        this.$emit("on-open-notice", data.username);
                                        this.clickDialog(data.username);
                                    }
                                }
                            }, [
                                h('img', { class: 'chat-notice-userimg', attrs: { src: data.userimg } }),
                                h('div', { class: 'ivu-notice-with-desc' }, [
                                    h('div', { class: 'ivu-notice-title' }, [
                                        h('UserView', { props: { username: data.username } })
                                    ]),
                                    h('div', { class: 'ivu-notice-desc' }, lasttext)
                                ])
                            ])
                        }
                    });
                }
            });
        },

        watch: {
            chatTap(val) {
                if (val === 'team' && this.teamReady == false) {
                    this.teamReady = true;
                    this.getTeamLists();
                } else if (val === 'dialog') {
                    this.autoBottom = true;
                    this.$nextTick(() => {
                        this.messageBottomGo();
                    });
                }
            },

            unreadTotal(val) {
                if (val < 0) {
                    this.unreadTotal = 0;
                    return;
                }
                this.$emit('input', val);
            },

            dialogTarget: {
                handler: function () {
                    let username = this.dialogTarget.username;
                    if (username === this.__dialogTargetUsername) {
                        return;
                    }
                    this.__dialogTargetUsername = username;
                    this.getDialogMessage();
                },
                deep: true
            }
        },

        computed: {
            dialogListsS() {
                return this.dialogLists.filter(item => {
                    return (item.username + "").indexOf(this.dialogSearch) > -1 || (item.lasttext + "").indexOf(this.dialogSearch) > -1 || (item.nickname + "").indexOf(this.dialogSearch) > -1
                });
            },
            teamListsS() {
                return function (lists) {
                    return lists.filter(item => {
                        return (item.username + "").indexOf(this.teamSearch) > -1 || (item.nickname + "").indexOf(this.teamSearch) > -1
                    });
                }
            }
        },

        methods: {
            formatCDate(v) {
                let string = '';
                if ($A.runNum(v) > 0) {
                    if ($A.formatDate('Ymd') === $A.formatDate('Ymd', v)) {
                        string = $A.formatDate('H:i', v)
                    } else if ($A.formatDate('Y') === $A.formatDate('Y', v)) {
                        string = $A.formatDate('m-d', v)
                    } else {
                        string = $A.formatDate('Y-m-d', v)
                    }
                }
                return string || '';
            },

            getDialogLists() {
                this.loadIng++;
                this.dialogNoDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'chat/dialog/lists',
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.dialogNoDataText = this.$L("数据加载失败！");
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.dialogLists = res.data;
                            this.dialogNoDataText = this.$L("没有相关的数据");
                        } else {
                            this.dialogLists = [];
                            this.dialogNoDataText = res.msg
                        }
                    }
                });
            },

            getDialogMessage(isNextPage = false) {
                if (isNextPage === true) {
                    if (!this.messageHasMorePages) {
                        return;
                    }
                    this.messageCurrentPage+= 1;
                } else {
                    this.messageCurrentPage = 1;
                    this.autoBottom = true;
                    this.messageNew = 0;
                    this.messageLists = [];
                }
                this.messageHasMorePages = false;
                //
                let username = this.dialogTarget.username;
                this.loadIng++;
                this.messageNoDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'chat/message/lists',
                    data: {
                        username: username,
                        page: this.messageCurrentPage,
                        pagesize: 30
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.messageNoDataText = this.$L("数据加载失败！");
                    },
                    success: (res) => {
                        if (username != this.dialogTarget.username) {
                            return;
                        }
                        if (res.ret === 1) {
                            let tempId = "notice_" + $A.randomString(6);
                            let tempLists = res.data.lists;
                            if (isNextPage) {
                                this.addMessageData({
                                    id: tempId,
                                    type: 'notice',
                                    notice: '历史消息',
                                }, false, isNextPage);
                            } else {
                                tempLists = tempLists.reverse();
                            }
                            tempLists.forEach((item) => {
                                this.addMessageData(Object.assign(item.message, {
                                    id: item.id,
                                    username: item.username,
                                    userimg: item.userimg,
                                    indate: item.indate,
                                }), false, isNextPage);
                            });
                            if (isNextPage) {
                                this.$nextTick(() => {
                                    let tempObj = $A('div[data-id="' + tempId + '"]');
                                    if (tempObj.length > 0) {
                                        this.$refs.manageLists.scrollTo(tempObj.offset().top - tempObj.height() - 24, false);
                                    }
                                });
                            }
                            this.messageNoDataText = '';
                            this.messageHasMorePages = res.data.hasMorePages;
                        } else {
                            this.messageNoDataText = res.msg
                            this.messageHasMorePages = false;
                        }
                    }
                });
            },

            getTeamLists(isNextPage = false) {
                if (isNextPage === true) {
                    if (!this.teamHasMorePages) {
                        return;
                    }
                    this.teamCurrentPage+= 1;
                } else {
                    this.teamCurrentPage = 1;
                }
                this.teamHasMorePages = false;
                //
                this.loadIng++;
                this.teamNoDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'users/team/lists',
                    data: {
                        sorts: {
                            key: 'username',
                            order: 'asc'
                        },
                        firstchart: 1,
                        page: this.teamCurrentPage,
                        pagesize: 100,
                    },
                    complete: () => {
                        this.loadIng--;
                    },
                    error: () => {
                        this.teamNoDataText = this.$L("数据加载失败！");
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            res.data.lists.forEach((item) => {
                                if (typeof this.teamLists[item.firstchart] === "undefined") {
                                    this.$set(this.teamLists, item.firstchart, []);
                                }
                                this.teamLists[item.firstchart].push(item);
                            });
                            this.teamNoDataText = this.$L("没有相关的数据");
                            this.teamHasMorePages = res.data.hasMorePages;
                            //
                            if (this.teamHasMorePages && res.data.currentPage < 5) {
                                this.getTeamLists(true);
                            }
                        } else {
                            this.teamLists = {};
                            this.teamNoDataText = res.msg
                            this.teamHasMorePages = false;
                        }
                    }
                });
            },

            addDialog(data, plusUnread = false) {
                if (!data.username) {
                    return;
                }
                let lists = this.dialogLists.filter((item) => {return item.username == data.username});
                if (lists.length > 0) {
                    data.unread = $A.runNum(lists[0].unread);
                    this.dialogLists = this.dialogLists.filter((item) => {return item.username != data.username});
                } else {
                    data.unread = 0;
                }
                if (plusUnread) {
                    data.unread += 1;
                    this.unreadTotal += 1;
                }
                this.dialogLists.unshift(data);
            },

            openDialog(user, autoAddDialog = false) {
                if (autoAddDialog === true) {
                    let lists = this.dialogLists.filter((item) => {return item.username == user.username});
                    if (lists.length === 0) {
                        this.addDialog(user);
                    }
                }
                this.chatTap = 'dialog';
                this.dialogTarget = user;
                if (typeof user.unread === "number" && user.unread > 0) {
                    this.unreadTotal -= user.unread;
                    this.$set(user, 'unread', 0);
                    $A.WS.sendTo('read', user.username);
                }
            },

            clickDialog(username) {
                let lists = this.dialogLists.filter((item) => {return item.username == username});
                if (lists.length > 0) {
                    this.openDialog(lists[0]);
                }
            },

            dialogDropdown(type) {
                switch (type) {
                    case 'clear':
                    case 'delete':
                        this.$Modal.confirm({
                            title: '确认操作',
                            content: type === 'delete' ? '你确定要删除此对话吗？' : '你确定要清除聊天记录吗？',
                            loading: true,
                            onOk: () => {
                                let username = this.dialogTarget.username;
                                $A.aAjax({
                                    url: 'chat/dialog/clear',
                                    data: {
                                        username: username,
                                        delete: type === 'delete' ? 1 : 0
                                    },
                                    error: () => {
                                        this.$Modal.remove();
                                        alert(this.$L('网络繁忙，请稍后再试！'));
                                    },
                                    success: (res) => {
                                        this.$Modal.remove();
                                        if (res.ret === 1) {
                                            if (type === 'delete') {
                                                this.dialogLists = this.dialogLists.filter((item) => {return item.username != username});
                                                this.dialogTarget = {};
                                            } else {
                                                this.$set(this.dialogTarget, 'lasttext', '');
                                                this.getDialogMessage();
                                            }
                                        }
                                        setTimeout(() => {
                                            if (res.ret === 1) {
                                                this.$Message.success(res.msg);
                                            } else {
                                                this.$Modal.error({title: this.$L('温馨提示'), content: res.msg});
                                            }
                                        }, 350);
                                    }
                                });
                            }
                        });
                        break;
                }
            },

            messageListsScroll(res) {
                if (res.directionreal === 'up') {
                    if (res.scrollE < 10) {
                        this.autoBottom = true;
                    }
                } else if (res.directionreal === 'down') {
                    this.autoBottom = false;
                }
            },

            messageBottomAuto() {
                let randString = $A.randomString(8);
                window.__messageBottomAuto = randString;
                setTimeout(() => {
                    if (randString === window.__messageBottomAuto) {
                        window.__messageBottomAuto = null;
                        if (this.autoBottom) {
                            this.messageBottomGo();
                        }
                        this.messageBottomAuto();
                    }
                }, 1000);
            },

            messageBottomGo(animation = false) {
                this.$nextTick(() => {
                    this.messageNew = 0;
                    if (typeof this.$refs.manageLists !== "undefined") {
                        this.$refs.manageLists.scrollTo(this.$refs.manageBody.clientHeight, animation);
                        this.autoBottom = true;
                    }
                });
            },

            messageInsertText(emoji) {
                this.messageText+= emoji;
            },

            messageInsertImage(lists) {
                for (let i = 0; i < lists.length; i++) {
                    let item = lists[i];
                    if (typeof item === 'object' && typeof item.url === "string") {
                        let data = {
                            type: 'image',
                            username: this.userInfo.username,
                            userimg: this.userInfo.userimg,
                            indate: Math.round(new Date().getTime() / 1000),
                            url: item.url
                        };
                        $A.WS.sendTo('user', this.dialogTarget.username, data, (res) => {
                            this.$set(data, res.status === 1 ? 'id' : 'error', res.message)
                        });
                        //
                        this.addDialog(Object.assign(this.dialogTarget, {
                            lasttext: '[图片]',
                            lastdate: data.indate
                        }));
                        this.openDialog(this.dialogTarget);
                        this.addMessageData(data, true);
                    }
                }
            },

            addMessageData(data, animation = false, isUnshift = false) {
                data.self = data.username === this.userInfo.username;
                let sikp = false;
                if (data.id) {
                    this.messageLists.some((item, index) => {
                        if (item.id == data.id) {
                            this.messageLists.splice(index, 1, data);
                            return sikp = true;
                        }
                    });
                    if (sikp) {
                        return;
                    }
                }
                if (isUnshift) {
                    this.messageLists.unshift(data);
                } else {
                    this.messageLists.push(data);
                    if (this.autoBottom) {
                        this.messageBottomGo(animation);
                    } else {
                        this.messageNew++;
                    }
                }
            },

            messageSend(e) {
                if (e.keyCode == 13) {
                    if (e.shiftKey) {
                        return;
                    }
                    e.preventDefault();
                    this.messageSubmit();
                }
            },

            messageSubmit() {
                let text = this.messageText.trim();
                if ($A.count(text) > 0) {
                    let data = {
                        type: 'text',
                        username: this.userInfo.username,
                        userimg: this.userInfo.userimg,
                        indate: Math.round(new Date().getTime() / 1000),
                        text: text
                    };
                    $A.WS.sendTo('user', this.dialogTarget.username, data, (res) => {
                        this.$set(data, res.status === 1 ? 'id' : 'error', res.message)
                    });
                    //
                    this.addDialog(Object.assign(this.dialogTarget, {
                        lasttext: text,
                        lastdate: data.indate
                    }));
                    this.openDialog(this.dialogTarget);
                    this.addMessageData(data, true);
                }
                this.$nextTick(() => {
                    this.messageText = "";
                });
            },
        }
    }
</script>
