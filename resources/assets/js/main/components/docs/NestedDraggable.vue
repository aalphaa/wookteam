<template>
    <draggable tag="div"
               :list="lists"
               :group="{ name: 'docs-nested' }"
               :animation="150"
               :disabled="disabled"
               @sort="handleClick('sort')">
        <div v-for="detail in lists" :key="detail.id" class="docs-group">
            <div class="item">
                <div class="dashed"></div>
                <div class="header">
                    <div class="tip"><Icon type="ios-folder-outline" /></div>
                    <div class="title">{{ detail.title }}</div>
                </div>
                <div class="info">
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
                @change="handleClick"/>
        </div>
    </draggable>
</template>
<style lang="scss" scoped>
    .docs-group {
        cursor: move;
        .docs-group {
            padding-left: 14px;
            background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAcCAYAAAByDd+UAAABS2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMxNDAgNzkuMTYwNDUxLCAyMDE3LzA1LzA2LTAxOjA4OjIxICAgICAgICAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIi8+CiA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgo8P3hwYWNrZXQgZW5kPSJyIj8+LUNEtwAAAEtJREFUSIntzzEVwAAMQkFSKfi3FKzQqQ5oJm5h5P3ZXQMYkrgwtk+OPo8kSzo7bGFcC+NaGNfCuBbGtTCuhXEtzB+SHAAGAEm/7wv2LKvDNoBjfgAAAABJRU5ErkJggg==) no-repeat -2px -9px;
            margin-left: 12px;
            border-left: 1px dotted #ddd;
        }
        .item {
            padding: 4px 0;
            background-color: #ffffff;
            border: solid 1px #ffffff;
            line-height: 24px;
            position: relative;
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
                    > i {
                        display: inline-block;
                    }
                }
                .title {
                    display: inline-block;
                    border-bottom: 1px solid transparent;
                    cursor: pointer;
                    padding: 0 4px;
                    color: #555555;
                }
            }
            .info {
                position: absolute;
                background: #fff;
                padding-left: 12px;
                color: #666;
                right: 0;
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
            }
        },
        data() {
            return {
                listSortData: '',
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
                if (act == 'sort') {
                    if (!this.isChildren) {
                        let tempSortData = this.getSort(this.lists);
                        if (tempSortData != this.listSortData) {
                            this.listSortData = tempSortData;
                            this.$emit("change", act, tempSortData)
                        }
                    }
                    return;
                }
                this.$emit("change", act, detail)
            }
        }
    };
</script>
