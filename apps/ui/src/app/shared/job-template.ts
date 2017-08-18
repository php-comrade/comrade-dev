import {Trigger} from "./trigger";

export class JobTemplate {
    schema: string = "http://jm.forma-pro.com/schemas/JobTemplate.json";
    name: string;
    templateId: string;
    processTemplateId: string;
    createdAt: Date;
    details: any;
    triggers: Trigger[] = [];

    addTrigger(trigger: Trigger)
    {
        this.triggers = [...this.triggers, trigger];
    }

    removeTrigger(trigger: Trigger)
    {
        this.triggers = this.triggers.filter(currentTrigger => currentTrigger !== trigger);
    }
}
