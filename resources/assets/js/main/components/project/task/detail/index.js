import Vue from 'vue';
import component from './detail.vue'

const detailElement = (taskid, detail = {}) => {
    let cloneData = (myObj) => {
        if (typeof (myObj) !== 'object') return myObj;
        if (myObj === null) return myObj;
        //
        if (typeof myObj.length === 'number') {
            let [...myNewObj] = myObj;
            return myNewObj;
        } else {
            let {...myNewObj} = myObj;
            return myNewObj;
        }
    };
    return new Promise(() => {
        let custom = Vue.extend(component);

        if (typeof taskid === 'object' && taskid !== null) {
            detail = cloneData(taskid);
            taskid = parseInt(taskid.id);
            if (isNaN(taskid)) {
                taskid = 0;
            }
        }

        if (typeof detail !== 'object' || detail === null) {
            detail = {}
        }

        if (typeof taskid === "number") {
            detail.taskid = taskid;
        }

        let data = {
            taskid: taskid,
            detail: detail,
        };

        let instance = new custom({
            data: data
        });

        instance.$mount();
        document.body.appendChild(instance.$el);
    })
};

export default detailElement

