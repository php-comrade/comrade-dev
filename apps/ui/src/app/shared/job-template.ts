import {Trigger} from "./trigger";
import {Runner} from "./runner";
import {Date} from "./date";
import {ExclusivePolicy} from "./exclusive-policy";

export class JobTemplate {
    schema: string = "http://jm.forma-pro.com/schemas/JobTemplate.json";
    name: string;
    templateId: string;
    processTemplateId: string;
    createdAt: Date;
    details: any;
    triggers: Trigger[] = [];
    runner: Runner;
    exclusivePolicy: ExclusivePolicy;

    addTrigger(trigger: Trigger)
    {
        this.triggers = [...this.triggers, trigger];
    }

    removeTrigger(trigger: Trigger)
    {
        this.triggers = this.triggers.filter(currentTrigger => currentTrigger !== trigger);
    }
}
