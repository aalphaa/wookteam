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
         * @param callback          网络请求获取到用户信息回调（监听用户信息发生变化）
         * @param onlyListener      只监听不重新网络请求获取
         * @returns Object
         */
        getUserInfo(callback, onlyListener) {
            if (typeof callback === 'function' || (typeof callback === "boolean" && callback === true)) {
                if (onlyListener === true) {
                    typeof callback === "function" && callback($A.jsonParse($A.storage("userInfo")));
                    $A.setOnUserInfoListener(callback);
                } else {
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
                                //
                                typeof callback === "function" && callback(res.data);
                            }
                            $A.setOnUserInfoListener(callback);
                        }
                    });
                }
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
         * @param callback
         */
        setOnUserInfoListener(callback) {
            if (typeof callback === "function") {
                $A.__userInfoListener.push(callback);
            }
        },
        triggerUserInfoListener(userInfo) {
            $A.__userInfoListener.forEach((callback) => {
                typeof callback === "function" && callback(userInfo);
            });
        },
        __userInfoListener: [],

        /**
         * 监听任务发生变化
         * @param callback
         */
        setOnTaskInfoListener(callback) {
            if (typeof callback === "function") {
                $A.__taskInfoListener.push(callback);
            }
        },
        triggerTaskInfoListener(act, taskDetail) {
            $A.__taskInfoListener.forEach((callback) => {
                typeof callback === "function" && callback(act, taskDetail);
            });
        },
        __taskInfoListener: [],

    });

    window.$A = $;
})(window);
