<template>
    <div :class="[count(val) > 0 ? 'item-title-active' : 'item-title']"><slot> </slot></div>
</template>

<script>
    export default {
        name: 'sreach-title',
        props: {
            val: { },
        },
        methods: {
            count(obj) {
                try {
                    if (typeof obj === "undefined") {
                        return 0;
                    }
                    if (typeof obj === "number" || obj instanceof Date) {
                        obj+= "";
                    }
                    if (typeof obj.length === 'number') {
                        if (typeof obj === 'object') {
                            let i = 0
                            $A.each(obj, (key, val) => {
                                if (this.count(val) > 0) i++;
                            });
                            return i;
                        }
                        return obj.length;
                    } else {
                        let i = 0, key;
                        for (key in obj) {
                            if (this.count(obj) > 0) i++;
                        }
                        return i;
                    }
                }catch (e) {
                    return 0;
                }
            },
        }
    }
</script>
