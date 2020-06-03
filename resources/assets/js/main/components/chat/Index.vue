<template>
    <div class="chat-index">

        <!--左边选项-->
        <ul class="chat-menu">
            <li class="self">
                <img :src="userInfo.userimg">
            </li>
            <li :class="{active:chatTap=='dialog'}" @click="chatTap='dialog'"><Icon type="md-text" /></li>
            <li :class="{active:chatTap=='team'}" @click="chatTap='team'"><Icon type="md-person" /></li>
        </ul>

        <!--对话列表-->
        <ul v-if="chatTap=='dialog'" class="chat-user">
            <li class="sreach">
                <Input placeholder="搜索" prefix="ios-search"/>
            </li>
            <li v-for="(dialog, index) in dialogLists"
                :key="index"
                :class="{active:dialog.username==dialogTarget.username}"
                @click="openDialog(dialog)">
                <img :src="dialog.userimg">
                <div class="user-msg-box">
                    <div class="user-msg-title">
                        <span><user-view :username="dialog.username"/></span>
                        <em>{{formatCDate(dialog.lastdate)}}</em>
                    </div>
                    <div class="user-msg-text">{{dialog.lasttext}}</div>
                </div>
            </li>
            <li v-if="dialogLists.length == 0" class="chat-none">{{dialogNoDataText}}</li>
        </ul>

        <!--联系人列表-->
        <ul v-else-if="chatTap=='team'" class="chat-team">
            <li class="sreach">
                <Input placeholder="搜索" prefix="ios-search"/>
            </li>
            <li v-for="(lists, key) in teamLists">
                <div class="team-label">{{key}}</div>
                <ul>
                    <li v-for="(item, index) in lists" :key="index" @click="openDialog(item)">
                        <img :src="item.userimg">
                        <div class="team-username"><user-view :username="item.username"/></div>
                    </li>
                </ul>
            </li>
            <li v-if="Object.keys(teamLists).length == 0" class="chat-none">{{teamNoDataText}}</li>
        </ul>

        <!--对话窗口-->
        <div v-if="chatTap=='dialog' && dialogTarget.username" class="chat-message">
            <div class="manage-title">
                <user-view :username="dialogTarget.username"/>
                <Dropdown class="manage-title-right" placement="bottom-end" trigger="click" transfer>
                    <Icon type="ios-more"/>
                    <DropdownMenu slot="list">
                        <DropdownItem name="clear">清除聊天记录</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </div>
            <ScrollerY ref="manageLists" class="manage-lists" @on-scroll="messageListsScroll">
                <div ref="manageBody" class="manage-body">
                    <chat-message v-for="(info, index) in messageLists" :key="index" :info="info"></chat-message>
                </div>
                <div class="manage-lists-message-new" v-if="messageNew > 0" @click="messageBottomGo(true)">有{{messageNew}}条新消息</div>
            </ScrollerY>
            <div class="manage-send">
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
            &:before {
                display: none;
            }
        }
        .chat-menu {
            background-color: rgba(28, 29, 31, 0.92);
            width: 68px;
            height: 100%;
            padding-top: 20px;
            li {
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
            }
        }
        .chat-user {
            width: 248px;
            height: 100%;
            background-color: #ffffff;
            border-right: 1px solid #ededed;
            li {
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
                &.sreach {
                    height: 62px;
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
        .chat-team {
            width: 248px;
            height: 100%;
            background-color: #ffffff;
            border-right: 1px solid #ededed;
            > li {
                margin-left: 24px;
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
        data () {
            return {
                loadIng: 0,

                userInfo: {},

                chatTap: 'dialog',

                dialogTarget: {},
                dialogLists: [],
                dialogNoDataText: '',

                teamReady: false,
                teamLists: {},
                teamNoDataText: '',

                autoBottom: true,
                messageNew: 0,
                messageText: '',
                messageLists: [],
                messageNoDataText: '',
                messageEmojiSearch: '',
            }
        },

        created() {
            this.dialogNoDataText = this.$L("数据加载中.....");
            this.teamNoDataText = this.$L("数据加载中.....");
            this.messageNoDataText = this.$L("数据加载中.....");
        },

        mounted() {
            this.userInfo = $A.getUserInfo((res) => {
                this.userInfo = res;
            }, false);
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
                let lasttext;
                switch (data.type) {
                    case 'text':
                        lasttext = data['text'];
                        break;
                    case 'image':
                        lasttext = '[图片]';
                        break;
                    default:
                        lasttext = '[未知类型]';
                        break;
                }
                this.openDialog({
                    username: data.username,
                    userimg: data.userimg,
                }, {
                    lasttext: lasttext,
                    lastdate: data.indate
                });
                if (msgDetail.sender == this.dialogTarget.username) {
                    this.addMessageData(data, true);
                }
            })
            //
            this.getDialogLists();
            this.messageBottomAuto();
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
            dialogTarget: {
                handler: function () {
                    this.getDialogMessage();
                },
                deep: true
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

            getDialogMessage() {
                let username = this.dialogTarget.username;
                if (username === this.__dialogTargetUsername) {
                    return;
                }
                this.__dialogTargetUsername = username;
                this.autoBottom = true;
                this.messageNew = 0;
                this.messageLists = [];
                //
                this.loadIng++;
                this.messageNoDataText = this.$L("数据加载中.....");
                $A.aAjax({
                    url: 'chat/message/lists',
                    data: {
                        username: username,
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
                            res.data.lists.reverse().forEach((item) => {
                                this.addMessageData(Object.assign(item.message, {
                                    id: item.id,
                                    username: item.username,
                                    userimg: item.userimg,
                                    indate: item.indate,
                                }));
                            });
                            this.messageNoDataText = this.$L("没有相关的数据");
                        } else {
                            this.messageNoDataText = res.msg
                        }
                    }
                });
            },

            getTeamLists() {
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
                                console.log(this.teamLists);
                            });
                            this.teamNoDataText = this.$L("没有相关的数据");
                        } else {
                            this.teamLists = {};
                            this.teamNoDataText = res.msg
                        }
                    }
                });
            },

            openDialog(user, lastMsg) {
                if (typeof lastMsg === "object") {
                    user = Object.assign(user, lastMsg);
                    this.dialogLists = this.dialogLists.filter((item) => {return item.username != user.username});
                }
                let lists = this.dialogLists.filter((item) => {return item.username == user.username});
                if (lists.length === 0) {
                    this.dialogLists.unshift(user);
                }
                if (typeof lastMsg !== "object") {
                    this.chatTap = 'dialog';
                    this.dialogTarget = user;
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
                }, 1200);
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
                        this.openDialog(this.dialogTarget, {
                            lasttext: '[图片]',
                            lastdate: data.indate
                        });
                        this.addMessageData(data, true);
                    }
                }
            },

            addMessageData(data, animation = false) {
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
                this.messageLists.push(data);
                //
                if (this.autoBottom) {
                    this.messageBottomGo(animation);
                } else {
                    this.messageNew++;
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
                    this.openDialog(this.dialogTarget, {
                        lasttext: text,
                        lastdate: data.indate
                    });
                    this.addMessageData(data, true);
                }
                this.$nextTick(() => {
                    this.messageText = "";
                });
            },
        }
    }
</script>
