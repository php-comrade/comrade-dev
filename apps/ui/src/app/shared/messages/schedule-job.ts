import {Trigger} from "../trigger";

export class ScheduleJob {
    public schema: string = 'http://comrade.forma-pro.com/schemas/message/ScheduleJob.json';

    constructor(public trigger: Trigger) {}
}