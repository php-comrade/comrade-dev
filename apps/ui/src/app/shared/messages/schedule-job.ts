import {Trigger} from "../trigger";

export class ScheduleJob {
    public schema: string = 'http://jm.forma-pro.com/schemas/message/ScheduleJob.json';

    constructor(public trigger: Trigger) {}
}