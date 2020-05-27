<template>
    <draggable tag="div"
               :list="lists"
               :group="{ name: 'docs-nested' }"
               :animation="150"
               :disabled="disabled || readonly"
               @sort="handleClick('sort')">
        <div v-for="detail in lists" :key="detail.id" class="docs-group" :class="{readonly:readonly,'hidden-children':detail.hiddenChildren===true}">
            <div class="item">
                <Icon class="together" type="md-add" @click="handleClick('open', detail)"/>
                <div class="dashed"></div>
                <div class="header">
                    <div class="tip"><img :src="detail.icon"/></div>
                    <div class="title" :class="{active:activeid==detail.id}" @click="handleClick('open', detail)">{{ detail.title }}</div>
                </div>
                <div v-if="!readonly" class="info">
                    <Icon type="md-create" @click="handleClick('edit', detail)"/>
                    <Icon type="md-add" @click="handleClick('add', detail)"/>
                    <Icon type="md-trash" @click="handleClick('delete', detail)"/>
                </div>
            </div>
            <nested-draggable
                v-if="typeof detail.children === 'object' && detail.children !== null"
                :lists="detail.children"
                :isChildren="true"
                :disabled="disabled"
                :readonly="readonly"
                :activeid="activeid"
                @change="handleClick"/>
        </div>
    </draggable>
</template>
<style lang="scss" scoped>
    .docs-group {
        cursor: move;
        &.readonly {
            cursor: default;
        }
        &.hidden-children {
            .docs-group {
                display: none;
            }
            .item {
                .together {
                    display: block;
                }
            }
        }
        .docs-group {
            padding-left: 14px;
            background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAcCAYAAAByDd+UAAABS2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMxNDAgNzkuMTYwNDUxLCAyMDE3LzA1LzA2LTAxOjA4OjIxICAgICAgICAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIi8+CiA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgo8P3hwYWNrZXQgZW5kPSJyIj8+LUNEtwAAAEtJREFUSIntzzEVwAAMQkFSKfi3FKzQqQ5oJm5h5P3ZXQMYkrgwtk+OPo8kSzo7bGFcC+NaGNfCuBbGtTCuhXEtzB+SHAAGAEm/7wv2LKvDNoBjfgAAAABJRU5ErkJggg==) no-repeat -2px -9px;
            margin-left: 18px;
            border-left: 1px dotted #ddd;
        }
        .item {
            padding: 4px 0 0 4px;
            background-color: #ffffff;
            border: solid 1px #ffffff;
            line-height: 24px;
            position: relative;
            .together {
                display: none;
                cursor: pointer;
                position: absolute;
                font-size: 12px;
                color: #ffb519;
                top: 50%;
                left: -2px;
                margin-top: 1px;
                transform: translate(0, -50%);
                z-index: 1;
            }
            .dashed {
                position: absolute;
                margin: 0;
                padding: 0;
                top: 16px;
                right: 0;
                left: 20px;
                height: 2px;
                border-width: 0 0 1px 0;
                border-bottom: dashed 1px #eee;
            }
            .header {
                display: inline-block;
                position: relative;
                background: #fff;
                padding: 0 8px;
                cursor: pointer;
                .tip {
                    display: inline-block;
                    position: relative;
                    > img {
                        display: inline-block;
                        width: 14px;
                        height: 14px;
                        margin-top: 5px;
                        vertical-align: top;
                    }
                }
                .title {
                    display: inline-block;
                    border-bottom: 1px solid transparent;
                    cursor: pointer;
                    padding: 0 3px;
                    color: #555555;
                    &.active {
                        color: #0396f2;
                    }
                }
            }
            .info {
                position: absolute;
                background: #fff;
                padding-left: 12px;
                color: #666;
                right: 3px;
                top: 5px;
                > i {
                    padding: 0 2px;
                    transition: all 0.2s;
                    cursor: pointer;
                    &:hover {
                        transform: scale(1.2);
                    }
                }
            }
        }
    }
</style>
<script>
    import draggable from "vuedraggable";

    export default {
        name: "NestedDraggable",
        props: {
            lists: {
                required: true,
                type: Array
            },
            isChildren: {
                type: Boolean,
                default: false,
            },
            disabled: {
                type: Boolean,
                default: false,
            },
            readonly: {
                type: Boolean,
                default: false,
            },
            activeid: {
                default: '',
            }
        },
        data() {
            return {
                listSortData: '',
                childrenHidden: false,
            }
        },
        components: {
            draggable
        },
        mounted() {
            this.listSortData = this.getSort(this.lists);
        },
        methods: {
            getSort(lists, parentid = 0) {
                let sortData = "";
                lists.forEach((item) => {
                    sortData+= item.id + ":" + parentid + ";" + this.getSort(item.children, item.id);
                });
                return sortData;
            },

            handleClick(act, detail) {
                if (act == 'open') {
                    if (detail.type == 'folder') {
                        this.$set(detail, 'hiddenChildren', !detail.hiddenChildren)
                        return;
                    }
                }
                if (act == 'sort') {
                    if (this.isChildren) {
                        this.$emit("change", act, detail);
                    } else {
                        let tempSortData = this.getSort(this.lists);
                        if (tempSortData != this.listSortData) {
                            this.listSortData = tempSortData;
                            this.$emit("change", act, tempSortData);
                        }
                    }
                    return;
                }
                this.$emit("change", act, detail)
            }
        }
    };
</script>
