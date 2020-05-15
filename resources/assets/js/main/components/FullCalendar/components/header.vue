<template>
    <div class="full-calendar-header">
        <div class="header-left">
            <slot name="header-left"></slot>
        </div>
        <div class="header-center">
            <span class="prev-month" @click.stop="goPrev">{{leftArrow}}</span>
            <span class="title">{{title}}</span>
            <span class="next-month" @click.stop="goNext">{{rightArrow}}</span>
        </div>
        <div class="header-right">
            <slot name="header-right"></slot>
        </div>
    </div>
</template>
<script type="text/babel">
    import dateFunc from './dateFunc'
    import bus from './bus'

    export default {
        created() {
            this.dispatchEvent(this.tableType)
        },
        props: {
            currentDate: {},
            titleFormat: {},
            firstDay: {},
            monthNames: {},
            tableType: ''
        },
        data() {
            return {
                title: '',
                leftArrow: '<',
                rightArrow: '>',
                headDate: new Date()
            }
        },
        watch: {
            currentDate(val) {
                if (!val) return
                this.headDate = val
            },
            tableType(val) {
                this.dispatchEvent(this.tableType)
            }
        },
        methods: {
            goPrev() {
                this.headDate = this.changeDateRange(this.headDate, -1)
                this.dispatchEvent(this.tableType)
            },
            goNext() {
                this.headDate = this.changeDateRange(this.headDate, 1)
                this.dispatchEvent(this.tableType)
            },
            changeDateRange(date, num) {
                let dt = new Date(date)
                if (this.tableType == 'month') {
                    return new Date(dt.setMonth(dt.getMonth() + num))
                } else {
                    return new Date(dt.valueOf() + num * 7 * 24 * 60 * 60 * 1000)
                }
            },
            dispatchEvent(type) {
                if (type == 'month') {
                    this.title = dateFunc.format(this.headDate, this.titleFormat, this.monthNames)
                    let startDate = dateFunc.getStartDate(this.headDate)
                    let curWeekDay = startDate.getDay()
                    // 1st day of this monthView
                    let diff = parseInt(this.firstDay) - curWeekDay
                    if (diff) diff -= 7
                    startDate.setDate(startDate.getDate() + diff)

                    // the month view is 6*7
                    let endDate = dateFunc.changeDay(startDate, 41)

                    // 1st day of current month
                    let currentDate = dateFunc.getStartDate(this.headDate)
                    this.$emit('change',
                        dateFunc.format(startDate, 'yyyy-MM-dd'),
                        dateFunc.format(endDate, 'yyyy-MM-dd'),
                        dateFunc.format(currentDate, 'yyyy-MM-dd'),
                        this.headDate
                    )
                } else if (type == 'week') {
                    let weekDays = dateFunc.getDates(this.headDate)

                    this.title = dateFunc.format(weekDays[0], 'MM-dd') + `  至  ` + dateFunc.format(weekDays[6], 'MM-dd')
                    this.$emit('change',
                        dateFunc.format(weekDays[0], 'yyyy-MM-dd'),
                        dateFunc.format(weekDays[6], 'yyyy-MM-dd'),
                        dateFunc.format(weekDays[0], 'yyyy-MM-dd'),
                        this.headDate,
                        weekDays
                    )
                    bus.$emit('changeWeekDays', weekDays)
                }
            },

        }
    }
</script>
<style lang="scss">
    .full-calendar-header {
        display: flex;
        align-items: center;

        .header-left, .header-right {
            flex: 1;
        }

        .header-center {
            flex: 3;
            text-align: center;
            user-select: none;
            font-weight: bold;

            .title {
                margin: 0 5px;
                width: 150px;
            }

            .prev-month, .next-month {
                cursor: pointer;
                padding: 10px 15px;
            }
        }
    }
</style>
