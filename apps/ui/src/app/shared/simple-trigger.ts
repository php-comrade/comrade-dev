import {Trigger} from "./trigger";
import {Date} from "./date";

export class SimpleTrigger extends Trigger {
    schema: string = 'http://comrade.forma-pro.com/schemas/trigger/SimpleTrigger.json';
    startAt: Date;
    intervalInSeconds: number;
    repeatCount: number;
    misfireInstruction: 'fire_now' | 'reschedule_now_with_existing_repeat_count' | 'reschedule_now_with_remaining_repeat_count' | 'reschedule_next_with_remaining_count' | 'reschedule_next_with_existing_count' | 'smart_policy' | 'ignore_misfire_policy';
}