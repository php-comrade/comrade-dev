import {Trigger} from "./trigger";

export class NowTrigger extends Trigger {
    schema: string = 'http://comrade.forma-pro.com/schemas/trigger/NowTrigger.json';
    payload: any;
}