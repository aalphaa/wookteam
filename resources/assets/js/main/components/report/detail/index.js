import Vue from 'vue';
import component from './detail.vue'

const detailElement = (reportid, reporttitle) => {
    return new Promise(() => {
        let custom = Vue.extend(component);

        let instance = new custom({
            data: {
                reportid: reportid,
                reporttitle: reporttitle
            }
        });

        instance.$mount();
        document.body.appendChild(instance.$el);
    })
};

export default detailElement

