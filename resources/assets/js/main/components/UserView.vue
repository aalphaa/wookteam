<template>
    <div class="user-view-inline">
        <Tooltip :disabled="nickname === null" :delay="delay" :transfer="transfer" :placement="placement" @on-popper-show="popperShow">
            {{nickname || username}}
            <div slot="content">
                <div>{{$L('用户名')}}: {{username}}</div>
                <div>{{$L('职位/职称')}}: {{profession || '-'}}</div>
            </div>
        </Tooltip>
    </div>
</template>

<style lang="scss" scoped>
    .user-view-inline {
        display: inline-block;
    }
</style>

<script>
    export default {
        name: 'UserView',
        props: {
            username: {
                default: ''
            },
            delay: {
                type: Number,
                default: 600
            },
            transfer: {
                type: Boolean,
                default: true
            },
            placement: {
                default: 'bottom'
            },
        },
        data() {
            return {
                nickname: null,
                profession: ''
            }
        },
        mounted() {
            this.getUserData(0, 300);
        },
        watch: {
            username() {
                this.getUserData(0, 300);
            },
            nickname(val) {
                this.$emit("on-result", val);
            }
        },
        methods: {
            popperShow() {
                this.getUserData(0, 60)
            },

            getUserData(num, cacheTime) {
                let keyName = '__name:' + this.username.substring(0, 1) + '__';
                let localData = $A.jsonParse(window.localStorage[keyName]);
                if (window.__userViewNetworking === true) {
                    if (num < 100) {
                        setTimeout(() => {
                            this.getUserData(num + 1, cacheTime)
                        }, 500);
                    }
                    return;
                }
                //
                if (typeof localData[this.username] !== "object") {
                    localData[this.username] = {};
                }
                //
                if (localData[this.username].success === true) {
                    this.nickname = localData[this.username].data.nickname;
                    this.profession = localData[this.username].data.profession;
                    if (localData[this.username].update + cacheTime > Math.round(new Date().getTime() / 1000)) {
                        return;
                    }
                }
                //
                window.__userViewNetworking = true;
                $A.aAjax({
                    url: 'users/basic',
                    data: {
                        username: this.username,
                    },
                    error: () => {
                        localData[this.username].success = false;
                        localData[this.username].update = 0;
                        localData[this.username].data = {};
                        window.localStorage[keyName] = $A.jsonStringify(localData);
                        window.__userViewNetworking = false;
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            this.nickname = res.data.nickname;
                            this.profession = res.data.profession;
                            localData[this.username].success = true;
                            localData[this.username].update = Math.round(new Date().getTime() / 1000);
                            localData[this.username].data = res.data;
                        } else {
                            this.nickname = '';
                            this.profession = '';
                            localData[this.username].success = false;
                            localData[this.username].update = 0;
                            localData[this.username].data = {};
                        }
                        window.localStorage[keyName] = $A.jsonStringify(localData);
                        window.__userViewNetworking = false;
                    }
                });
            }
        }
    }
</script>
