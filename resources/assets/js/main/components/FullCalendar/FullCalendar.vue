<template>
    <div class="comp-full-calendar">
        <!-- header pick month -->
        <fc-header :current-date="currentDate"
                   :title-format="titleFormat"
                   :first-day="firstDay"
                   :month-names="monthNames"
                   :tableType="tableType"
                   @change="changeDateRange">

            <div slot="header-left">
                <slot name="fc-header-left">
                </slot>
            </div>

            <div slot="header-right" class="btn-group">
                <div>
                    <button :class="{cancel:tableType=='month',primary:tableType=='week'}" @click="changeType('week')">
                        周
                    </button>
                    <button :class="{cancel:tableType=='week',primary:tableType=='month'}" @click="changeType('month')">
                        月
                    </button>
                </Div>
            </div>
        </fc-header>
        <!-- body display date day and events -->
        <fc-body :current-date="currentDate" :events="events" :month-names="monthNames" ref="fcbody"
                 :tableType="tableType"
                 :loading="loading"
                 :week-names="weekNames" :first-day="firstDay"
                 :weekDays="weekDays"
                 @eventclick="emitEventClick" @dayclick="emitDayClick"
                 @moreclick="emitMoreClick">
            <template slot="body-card">
                <slot name="fc-body-card">
                </slot>
            </template>
        </fc-body>
    </div>
</template>
<script type="text/babel">
    import langSets from './dataMap/langSets'
    import fcBody from './components/body'
    import fcHeader from './components/header'

    export default {
        name: "FullCalendar",
        components: {
            'fc-body': fcBody,
            'fc-header': fcHeader
        },
        props: {
            events: {
                type: Array,
                default: []
            },
            lang: {
                type: String,
                default: 'en'
            },
            firstDay: {
                type: Number | String,
                validator(val) {
                    let res = parseInt(val)
                    return res >= 0 && res <= 6
                },
                default: 0
            },
            titleFormat: {
                type: String,
                default() {
                    return langSets[this.lang].titleFormat
                }
            },
            monthNames: {
                type: Array,
                default() {
                    return langSets[this.lang].monthNames
                }
            },
            weekNames: {
                type: Array,
                default() {
                    let arr = langSets[this.lang].weekNames
                    return arr.slice(this.firstDay).concat(arr.slice(0, this.firstDay))
                }
            },
            loading: {
                type: Boolean,
                default: false
            },
        },
        data() {
            return {
                tableType: 'week',
                weekDays: [],
                currentDate: new Date()
            }
        },
        created() {

        },
        methods: {
            changeDateRange(start, end, currentStart, current, weekDays) {
                this.currentDate = current
                this.weekDays = weekDays;
                this.$emit('change', start, end, currentStart, weekDays)
            },
            emitEventClick(event, jsEvent) {
                this.$emit('eventClick', event, jsEvent)
            },
            emitDayClick(day, jsEvent) {
                this.$emit('dayClick', day, jsEvent)
            },
            emitMoreClick(day, events, jsEvent) {
                this.$emit('moreClick', day, event, jsEvent)
            },
            changeType(type) {
                this.tableType = type;
                this.$emit('changeType', type)
            },
        },
    }

</script>
<style lang="scss">
    .comp-full-calendar {
        // font-family: "elvetica neue", tahoma, "hiragino sans gb";
        // background: #fff;
        min-width: 960px;
        margin: 0 auto;

        ul, p {
            margin: 0;
            padding: 0;
            font-size: 14px;
        }

        .cancel {
            border: 0;
            outline: none;
            box-shadow: unset;
            background-color: #ECECED;
            color: #8B8F94;

            &:hover {
                color: #3E444C;
                z-index: 0;
            }
        }

        .primary {
            border: 0;
            outline: none;
            box-shadow: unset;
            background-color: #5272FF;
            color: #fff;

            &:hover {
                z-index: 0;
            }
        }

        .btn-group {
            width: 100%;
            display: flex;
            justify-content: flex-end;

            button {
                width: 80px;
                cursor: pointer;
                height: 26px;
            }
        }
    }
</style>
