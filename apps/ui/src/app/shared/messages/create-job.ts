import {JobTemplate} from "../job-template";
import {Trigger} from "../trigger";

export class CreateJob {
    public schema: string = 'http://jm.forma-pro.com/schemas/message/CreateJob.json';

    triggers: Trigger[] = [];

    constructor(public jobTemplate: JobTemplate) {}

    addTrigger(trigger: Trigger)
    {
        this.triggers = [...this.triggers, trigger];
    }

    removeTrigger(trigger: Trigger)
    {
        this.triggers = this.triggers.filter(currentTrigger => currentTrigger !== trigger);
    }
}