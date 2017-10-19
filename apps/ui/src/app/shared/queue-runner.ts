import {Runner} from "./runner";

export class QueueRunner extends Runner {
    schema: string = 'http://comrade.forma-pro.com/schemas/runner/QueueRunner.json';
    queue: string;
    connectionDsn: string;
}
