import {Component, EventEmitter, Output} from '@angular/core';
import {CronTrigger} from "../shared/cron-trigger";
import { DatePickerOptions, DateModel } from 'ng2-datepicker';
import {Date} from "../shared/date";
import {Trigger} from "../shared/trigger";

@Component({
  selector: 'new-cron-trigger',
  templateUrl: './new-cron.trigger.component.html',
})
export class NewCronTriggerComponent {
    @Output() onTriggerAdded = new EventEmitter<Trigger>();

    date: DateModel;
    expression: string;
    misfireInstruction: 'fire_once_now' | 'do_nothing' | 'smart_policy' | 'ignore_misfire_policy';

    options: DatePickerOptions;


    constructor() {
        this.options = new DatePickerOptions();

        let moment = require('moment');
        let now = moment();
        // this.options.minDate =now.toDate();
        this.options.initialDate = now.toDate();

        this.expression = '* * * * *';

        this.misfireInstruction = 'fire_once_now';
    }

    add() {
        let cronTrigger = new CronTrigger();
        cronTrigger.misfireInstruction = this.misfireInstruction;
        cronTrigger.startAt = Date.fromMoment(this.date.momentObj);
        cronTrigger.expression = this.expression;

        this.onTriggerAdded.emit(cronTrigger);
    }
}
