import {Trigger} from "../trigger";

export class AddTrigger {
    public schema: string = 'http://jm.forma-pro.com/schemas/message/AddTrigger.json';

    constructor(public jobTemplateId: string, public trigger: Trigger) {}
}