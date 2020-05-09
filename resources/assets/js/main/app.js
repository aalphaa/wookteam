import Vue from 'vue'
import App from './App.vue'
import routes from './routes'
import VueRouter from 'vue-router'
import ViewUI from 'view-design';
import Language from '../_modules/language'

import '../common'
import './main'

Vue.use(VueRouter);
Vue.use(ViewUI);
Vue.use(Language);

import Title from '../_components/Title.vue'
Vue.component('VTitle', Title);

const router = new VueRouter({routes});

//进度条配置
ViewUI.LoadingBar.config({
    color: '#3fcc25',
    failedColor: '#ff0000'
});
router.beforeEach((to, from, next) => {
    ViewUI.LoadingBar.start();
    next();
});
router.afterEach((to, from, next) => {
    ViewUI.LoadingBar.finish();
});

//加载函数
Vue.prototype.goForward = function(location, isReplace) {
    if (typeof location === 'string') location = {name: location};
    if (isReplace === true) {
        this.$router.replace(location);
    }else{
        this.$router.push(location);
    }
};

//返回函数
Vue.prototype.goBack = function(number) {
    window.history.go(typeof number==='number'?number:-1)
};

Vue.prototype.$A = $A;

Vue.config.productionTip = false;

const app = new Vue({
    el: '#app',
    router,
    template: '<App/>',
    components: { App }
});

$A.app = app;

