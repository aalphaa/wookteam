const languageListenerObjects = [];

export default {
    install(Vue) {
        Vue.mixin({
            data() {
                return {
                    privateLanguageInit: false,
                    privateLanguageData: {},
                    privateLanguageType: '',
                }
            },

            methods: {
                /**
                 * 初始化语言数据
                 * @private
                 */
                __initLanguageData() {
                    if (this.privateLanguageInit === false) {
                        this.privateLanguageInit = true;
                        //
                        this.addLanguageData({
                            en: require("./global/en.js").default,
                            zh: require("./global/zh.js").default
                        });
                        this.privateLanguageType = window.localStorage['__language:type__'] || 'zh';
                        //
                        languageListenerObjects.push((lang) => {
                            this.privateLanguageType = lang;
                        });
                    }
                },

                /**
                 * 监听语言变化
                 * @param callback
                 */
                setLanguageListener(callback) {
                    if (typeof callback === 'function') {
                        languageListenerObjects.push((lang) => {
                            callback(lang);
                        });
                    }
                },

                /**
                 * 语言包数据
                 * @param language
                 * @param data
                 */
                addLanguageData(language, data) {
                    if (typeof language === 'object') {
                        Object.keys(language).forEach((key) => {
                            this.addLanguageData(key, language[key]);
                        });
                        return;
                    }
                    if (!language || typeof data !== "object") {
                        return;
                    }
                    this.__initLanguageData();
                    if (typeof this.privateLanguageData[language] === "undefined") {
                        this.privateLanguageData[language] = {};
                    }
                    Object.assign(this.privateLanguageData[language], data);
                    //
                    if (language === 'en') {
                        if (typeof this.privateLanguageData['zh'] === "undefined") {
                            this.privateLanguageData['zh'] = {};
                        }
                        let cnData = {};
                        for(let key in data) {
                            if (data.hasOwnProperty(key) && typeof this.privateLanguageData['zh'][data[key]] === 'undefined') {
                                cnData[data[key]] = key;
                            }
                        }
                        Object.assign(this.privateLanguageData['zh'], cnData);
                    }else if (language === 'zh') {
                        if (typeof this.privateLanguageData['en'] === "undefined") {
                            this.privateLanguageData['en'] = {};
                        }
                        let enData = {};
                        for(let key in data) {
                            if (data.hasOwnProperty(key) && typeof this.privateLanguageData['en'][data[key]] === 'undefined') {
                                enData[data[key]] = key;
                            }
                        }
                        Object.assign(this.privateLanguageData['en'], enData);
                    }
                },

                /**
                 * 变化语言
                 * @param language
                 */
                setLanguage(language) {
                    this.__initLanguageData();
                    window.localStorage['__language:type__'] = language;
                    languageListenerObjects.forEach((call) => {
                        if (typeof call === 'function') {
                            call(language);
                        }
                    });
                },

                /**
                 * 获取语言
                 * @returns {*}
                 */
                getLanguage() {
                    this.__initLanguageData();
                    return this.privateLanguageType;
                },

                /**
                 * 显示语言
                 * @return {string}
                 */
                $L(text) {
                    if (text) {
                        this.__initLanguageData();
                        //
                        if (typeof this.privateLanguageData[this.privateLanguageType] === "object") {
                            let temp = this.privateLanguageData[this.privateLanguageType][text];
                            if (temp === null) {
                                return text;
                            }
                            if (typeof temp !== 'undefined') {
                                return temp;
                            }
                        }
                        //
                        try {
                            let key = '__language:Undefined__';
                            let tmpData = JSON.parse(window.localStorage[key] || '{}');
                            if (typeof tmpData[this.privateLanguageType] !== "object") {
                                tmpData[this.privateLanguageType] = {};
                            }
                            tmpData[this.privateLanguageType][text] = "";
                            window.localStorage[key] = JSON.stringify(tmpData);
                        }catch (e) {
                            //
                        }
                    }
                    return text;
                }
            }
        });
    }
}
