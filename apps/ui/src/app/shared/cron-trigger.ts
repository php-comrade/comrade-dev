import {Trigger} from "./trigger";
import {Date} from "./date";

export class CronTrigger extends Trigger {
    schema: string = 'http://jm.forma-pro.com/schemas/trigger/CronTrigger.json';
    startAt: Date;
    expression: string;
    misfireInstruction: 'fire_once_now' | 'do_nothing' | 'smart_policy' | 'ignore_misfire_policy';
}
