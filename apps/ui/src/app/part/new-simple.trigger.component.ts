import {Component, EventEmitter, Input, Output} from '@angular/core';
import { DatePickerOptions, DateModel } from 'ng2-datepicker';
import {Trigger} from "../shared/trigger";
import {SimpleTrigger} from "../shared/simple-trigger";
import {Date} from "../shared/date";

@Component({
  selector: 'new-simple-trigger',
  templateUrl: './new-simple.trigger.component.html',
})
export class NewSimpleTriggerComponent {
    @Output() onTriggerAdded = new EventEmitter<Trigger>();

    date: DateModel;
    repeatCount: number;
    intervalInSeconds: number;
    misfireInstruction: 'fire_now' | 'reschedule_now_with_existing_repeat_count' | 'reschedule_now_with_remaining_repeat_count' | 'reschedule_next_with_remaining_count' | 'reschedule_next_with_existing_count' | 'smart_policy' | 'ignore_misfire_policy';

    options: DatePickerOptions;

    constructor() {
        this.options = new DatePickerOptions();

        let moment = require('moment');
        let now = moment();
        this.options.initialDate = now.toDate();

        this.repeatCount = 0;
        this.intervalInSeconds = 0;

        this.misfireInstruction = 'fire_now';
    }

    add() {
        let simpleTrigger = new SimpleTrigger();
        simpleTrigger.misfireInstruction = this.misfireInstruction;
        simpleTrigger.startAt = Date.fromMoment(this.date.momentObj);
        simpleTrigger.repeatCount = this.repeatCount;
        simpleTrigger.intervalInSeconds = this.intervalInSeconds;

        this.onTriggerAdded.emit(simpleTrigger);
    }
}
