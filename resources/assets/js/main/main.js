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
                            typeof callback === "function" && callback(res.data);
                        }
                        //
                        if (typeof continueListenerName == "string" && continueListenerName) {
                            $A.setOnUserInfoListener(continueListenerName, callback);
                        }
                    }
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
                    item.callback(userInfo);
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
        triggerTaskInfoListener(act, taskDetail) {
            let key, item;
            for (key in $A.__taskInfoListenerObject) {
                if (!$A.__taskInfoListenerObject.hasOwnProperty(key)) continue;
                item = $A.__taskInfoListenerObject[key];
                if (typeof item.callback === "function") {
                    if (act.indexOf(['deleteproject', 'deletelabel', 'leveltask']) === -1 || item.special === true) {
                        item.callback(act, taskDetail);
                    }
                }
            }
        },
        __taskInfoListenerObject: {},

    });

    window.$A = $;
})(window);
