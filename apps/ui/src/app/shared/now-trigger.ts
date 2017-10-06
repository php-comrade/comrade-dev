import {Trigger} from "./trigger";

export class NowTrigger extends Trigger {
    schema: string = 'http://jm.forma-pro.com/schemas/trigger/NowTrigger.json';
    payload: any;
}