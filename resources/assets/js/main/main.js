/**
 * 页面专用
 */

import '../../sass/main.scss';

(function (window) {

    let apiUrl = window.location.origin + '/api/';
    let $ = window.$A;

    $.extend({

        fillUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            return window.location.origin + '/' + str;
        },

        aUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            return apiUrl + str;
        },

        aAjax(params) {
            if (typeof params !== 'object') return false;
            if (typeof params.success === 'undefined') params.success = () => { };
            params.url = this.aUrl(params.url);
            //
            let beforeCall = params.beforeSend;
            params.beforeSend = () => {
                $A.aAjaxLoad++;
                $A(".w-spinner").show();
                //
                if (typeof beforeCall == "function") {
                    beforeCall();
                }
            };
            //
            let completeCall = params.complete;
            params.complete = () => {
                $A.aAjaxLoad--;
                if ($A.aAjaxLoad <= 0) {
                    $A(".w-spinner").hide();
                }
                //
                if (typeof completeCall == "function") {
                    completeCall();
                }
            };
            //
            let callback = params.success;
            params.success = (data, status, xhr) => {
                if (typeof data === 'object') {
                    if (data.ret === -1 && params.checkRole !== false) {
                        //身份丢失
                        $A.app.$Modal.error({
                            title: '温馨提示',
                            content: data.msg,
                            onOk: () => {
                                $A.token("");
                                $A.userLogout();
                            }
                        });
                        return;
                    }
                    if (data.ret === -2 && params.role !== false) {
                        //没有权限
                        $A.app.$Modal.error({
                            title: '权限不足',
                            content: data.msg ? data.msg : "你没有相关的权限查看或编辑！"
                        });
                    }
                }
                if (typeof callback === "function") {
                    callback(data, status, xhr);
                }
            };
            //
            $A.ajax(params);
        },
        aAjaxLoad: 0,

        /**
         * 编辑器参数配置
         * @returns {{modules: {toolbar: *[]}}}
         */
        editorOption() {
            return {
                modules: {
                    toolbar: [
                        ['bold', 'italic'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'size': ['small', false, 'large', 'huge'] }],
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'align': [] }]
                    ]
                }
            };
        },

        /**
         * 获取token
         * @returns {boolean}
         */
        getToken() {
            let token = $A.token();
            return $A.count(token) < 10 ? false : token;
        },

        /**
         * 设置token
         * @param token
         */
        setToken(token) {
            $A.token(token);
        },

        /**
         * 获取会员昵称
         * @returns string
         */
        getUserName() {
            if ($A.getToken() === false) {
                return "";
            }
            let userInfo = $A.getUserInfo();
            return $A.ishave(userInfo.username) ? userInfo.username : '';
        },

        /**
         * 获取用户信息（并保存）
         * @param callback                  网络请求获取到用户信息回调（监听用户信息发生变化）
         * @param continueListenerName      持续监听标识（字符串或boolean，true:自动生成监听标识，false:自动生成监听标识但首次不请求网络）
         * @returns Object
         */
        getUserInfo(callback, continueListenerName) {
            if (typeof callback === 'function' || (typeof callback === "boolean" && callback === true)) {
                if (typeof continueListenerName === "boolean") {
                    if (continueListenerName === true) {
                        continueListenerName = "auto-" + $A.randomString(6);
                    } else {
                        $A.setOnUserInfoListener("auto-" + $A.randomString(6), callback);
                        return $A.jsonParse($A.storage("userInfo"));
                    }
                }
                //
                $A.aAjax({
                    url: 'users/info',
                    error: () => {
                        this.userLogout();
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            $A.storage("userInfo", res.data);
                            $A.setToken(res.data.token);
                            $A.triggerUserInfoListener(res.data);
                            typeof callback === "function" && callback(res.data, $A.getToken() !== false);
                        }
                    },
                    afterComplete: () => {
                        if (typeof continueListenerName == "string" && continueListenerName) {
                            $A.setOnUserInfoListener(continueListenerName, callback);
                        }
                    },
                });
            }
            return $A.jsonParse($A.storage("userInfo"));
        },

        /**
         * 打开登录页面
         */
        userLogout() {
            $A.token("");
            $A.storage("userInfo", {});
            $A.triggerUserInfoListener({});
            if (typeof $A.app === "object") {
                $A.app.goForward({path: '/'}, true);
            } else {
                window.location.href = window.location.origin;
            }
        },

        /**
         * 权限是否通过
         * @param role
         * @returns {boolean}
         */
        identity(role) {
            let userInfo = $A.getUserInfo();
            let identity = userInfo.identity;
            let isRole = false;
            $A.each(identity, (index, res) => {
                if (res === role) {
                    isRole = true;
                }
            });
            return isRole;
        },

        /**
         * 监听用户信息发生变化
         * @param listenerName      监听标识
         * @param callback          监听回调
         */
        setOnUserInfoListener(listenerName, callback) {
            if (typeof listenerName != "string") {
                return;
            }
            if (typeof callback === "function") {
                $A.__userInfoListenerObject[listenerName] = {
                    callback: callback,
                }
            }
        },
        triggerUserInfoListener(userInfo) {
            let key, item;
            for (key in $A.__userInfoListenerObject) {
                if (!$A.__userInfoListenerObject.hasOwnProperty(key)) continue;
                item = $A.__userInfoListenerObject[key];
                if (typeof item.callback === "function") {
                    item.callback(userInfo, $A.getToken() !== false);
                }
            }
        },
        __userInfoListenerObject: {},

        /**
         * 监听任务发生变化
         * @param listenerName      监听标识
         * @param callback          监听回调
         * @param callSpecial       是否监听几种特殊情况
         */
        setOnTaskInfoListener(listenerName, callback, callSpecial) {
            if (typeof listenerName != "string") {
                return;
            }
            if (typeof callback === "function") {
                $A.__taskInfoListenerObject[listenerName] = {
                    special: callSpecial === true,
                    callback: callback,
                }
            }
        },
        triggerTaskInfoListener(act, taskDetail, sendToWS = true) {
            let key, item;
            for (key in $A.__taskInfoListenerObject) {
                if (!$A.__taskInfoListenerObject.hasOwnProperty(key)) continue;
                item = $A.__taskInfoListenerObject[key];
                if (typeof item.callback === "function") {
                    if (['deleteproject', 'deletelabel', 'leveltask'].indexOf(act) === -1 || item.special === true) {
                        if (typeof taskDetail.__modifyUsername === "undefined") {
                            taskDetail.__modifyUsername = $A.getUserName();
                        }
                        item.callback(act, taskDetail);
                    }
                }
            }
            if (sendToWS === true) {
                $A.WS.sendTo('team', {
                    type: "taskA",
                    act: act,
                    taskDetail: taskDetail
                });
            }
        },
        __taskInfoListenerObject: {},

        /**
         * 获取待推送的日志并推送
         * @param taskid
         */
        triggerTaskInfoChange(taskid) {
            $A.aAjax({
                url: 'project/task/pushlog',
                data: {
                    taskid: taskid,
                    pagesize: 20
                },
                success: (res) => {
                    if (res.ret === 1) {
                        res.data.lists.forEach((item) => {
                            let msgData = {
                                type: 'taskB',
                                username: item.username,
                                userimg: item.userimg,
                                indate: item.indate,
                                text: item.detail,
                                other: item.other
                            };
                            res.data.follower.forEach((username) => {
                                if (username != msgData.username) {
                                    $A.WS.sendTo('user', username, msgData, 'special');
                                }
                            });
                        });
                    }
                }
            });
        }
    });

    /**
     * =============================================================================
     * *****************************   websocket assist   ****************************
     * =============================================================================
     */
    $.extend({
        WS: {
            __instance: null,
            __connected: false,
            __callbackid: {},
            __openNum: 0,
            __autoNum: 0,
            __autoLine(timeout) {
                let tempNum = this.__autoNum;
                setTimeout(() => {
                    if (tempNum === this.__autoNum) {
                        this.__autoNum++
                        if ($A.getToken() === false) {
                            console.log("[WS] No token");
                            this.__autoLine(timeout + 5);
                        } else {
                            this.sendTo('refresh', (res) => {
                                console.log("[WS] Connection " + (res.status ? 'success' : 'error'));
                                this.__autoLine(timeout + 5);
                            });
                        }
                    }
                }, Math.min(timeout, 30) * 1000);
            },

            /**
             * 连接
             */
            connection(force = false) {
                let url = $A.getObject(window.webSocketConfig, 'URL');
                url += ($A.strExists(url, "?") ? "&" : "?") + "token=" + $A.getToken();
                if (!$A.leftExists(url, "ws://") && !$A.leftExists(url, "wss://")) {
                    console.log("[WS] No connection address");
                    return;
                }

                if ($A.getToken() === false) {
                    console.log("[WS] No connected token");
                    return;
                }

                if (this.__instance !== null && force !== true) {
                    console.log("[WS] Connection exists");
                    return;
                }

                // 初始化客户端套接字并建立连接
                this.__instance = new WebSocket(url);

                // 连接建立时触发
                this.__instance.onopen = (event) => {
                    console.log("[WS] Connection opened");
                }

                // 接收到服务端推送时执行
                this.__instance.onmessage = (event) => {
                    let msgDetail = $A.jsonParse(event.data);
                    if (msgDetail.messageType === 'open') {
                        console.log("[WS] Connection connected");
                        msgDetail.openNum = this.__openNum;
                        this.__openNum++;
                        this.__connected = true;
                        this.__autoLine(30);
                    } else if (msgDetail.messageType === 'feedback') {
                        typeof this.__callbackid[msgDetail.messageId] === "function" && this.__callbackid[msgDetail.messageId](msgDetail.content);
                        delete this.__callbackid[msgDetail.messageId];
                        return;
                    }
                    if ($A.runNum(msgDetail.contentId) > 0) {
                        $A.WS.sendTo('roger', msgDetail.contentId);
                    }
                    this.triggerMsgListener(msgDetail);
                };

                // 连接关闭时触发
                this.__instance.onclose = (event) => {
                    console.log("[WS] Connection closed");
                    this.__connected = false;
                    this.__instance = null;
                    this.__autoLine(5);
                }

                // 连接出错
                this.__instance.onerror = (event) => {
                    console.log("[WS] Connection error");
                    this.__connected = false;
                    this.__instance = null;
                    this.__autoLine(5);
                }

                return this;
            },

            /**
             * 添加消息监听
             * @param listenerName
             * @param callback
             */
            setOnMsgListener(listenerName, callback) {
                if (typeof listenerName != "string") {
                    return;
                }
                if (typeof callback === "function") {
                    this.__msgListenerObject[listenerName] = {
                        callback: callback,
                    }
                }
                return this;
            },
            triggerMsgListener(msgDetail) {
                let key, item;
                for (key in this.__msgListenerObject) {
                    if (!this.__msgListenerObject.hasOwnProperty(key)) continue;
                    item = this.__msgListenerObject[key];
                    if (typeof item.callback === "function") {
                        item.callback(msgDetail);
                    }
                }
            },
            __msgListenerObject: {},

            /**
             * 添加特殊监听
             * @param listenerName
             * @param callback
             */
            setOnSpecialListener(listenerName, callback) {
                if (typeof listenerName != "string") {
                    return;
                }
                if (typeof callback === "function") {
                    this.__specialListenerObject[listenerName] = {
                        callback: callback,
                    }
                }
                return this;
            },
            triggerSpecialListener(content) {
                let key, item;
                for (key in this.__specialListenerObject) {
                    if (!this.__specialListenerObject.hasOwnProperty(key)) continue;
                    item = this.__specialListenerObject[key];
                    if (typeof item.callback === "function") {
                        item.callback(content);
                    }
                }
            },
            __specialListenerObject: {},

            /**
             * 发送消息
             * @param type      会话类型
             * - refresh: 刷新
             * - unread: 未读信息总数量
             * - read: 已读会员信息
             * - roger: 收到信息回执
             * - user: 指定target
             * - team: 团队会员
             * @param target    发送目标
             * @param content   发送内容（对象或数组）
             * @param callback  发送回调
             * @param againNum
             */
            sendTo(type, target, content, callback, againNum = 0) {
                if (typeof target === "object" && typeof content === "undefined") {
                    content = target;
                    target = null;
                }
                if (typeof target === "function") {
                    content = target;
                    target = null;
                }
                if (typeof content === "function") {
                    callback = content;
                    content = null;
                }
                //
                if (this.__instance === null) {
                    if (againNum < 10 && type != 'team') {
                        setTimeout(() => {
                            this.sendTo(type, target, content, callback, $A.runNum(againNum) + 1)
                        }, 600);
                        if (againNum === 0) {
                            this.connection();
                        }
                    } else {
                        console.log("[WS] Service not connected");
                        typeof callback === "function" && callback({status: 0, message: '服务未连接'});
                    }
                    return;
                }
                if (this.__connected === false) {
                    console.log("[WS] Failed connection");
                    typeof callback === "function" && callback({status: 0, message: '未连接成功'});
                    return;
                }
                if (['refresh', 'unread', 'read', 'roger', 'user', 'team'].indexOf(type) === -1) {
                    console.log("[WS] Wrong message type: " + type);
                    typeof callback === "function" && callback({status: 0, message: '错误的消息类型: ' + type});
                    return;
                }
                //
                let contentId = 0;
                if (type === 'roger') {
                    contentId = target;
                    target = null;
                }
                let messageId = '';
                if (typeof callback === "string" && callback === 'special') {
                    callback = (res) => {
                        res.status === 1 && this.triggerSpecialListener(content);
                    }
                }
                if (typeof callback === "function") {
                    messageId = $A.randomString(16);
                    this.__callbackid[messageId] = callback;
                }
                this.__instance.send(JSON.stringify({
                    messageType: 'send',
                    messageId: messageId,
                    contentId: contentId,
                    type: type,
                    sender: $A.getUserName(),
                    target: target,
                    content: content,
                    time: Math.round(new Date().getTime() / 1000),
                }));
                return this;
            },

            /**
             * 关闭连接
             */
            close() {
                if (this.__instance === null) {
                    console.log("[WS] Service not connected");
                    return;
                }
                if (this.__connected === false) {
                    console.log("[WS] Failed connection");
                    return;
                }
                this.__instance.close();
            },

            /**
             * 获取消息描述
             * @param content
             * @returns {string}
             */
            getMsgDesc(content) {
                let desc;
                switch (content.type) {
                    case 'text':
                        desc = content.text;
                        break;
                    case 'image':
                        desc = $A.app.$L('[图片]');
                        break;
                    case 'taskB':
                        desc = content.text + " " + $A.app.$L("[来自关注任务]");
                        break;
                    case 'report':
                        desc = content.text + " " + $A.app.$L("[来自工作报告]");
                        break;
                    default:
                        desc = $A.app.$L('[未知类型]');
                        break;
                }
                return desc;
            }
        }
    });

    window.$A = $;
})(window);
