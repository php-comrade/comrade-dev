import {Runner} from "./runner";

export class QueueRunner extends Runner {
    schema: string = 'http://jm.forma-pro.com/schemas/runner/QueueRunner.json';
    queue: string;
    connection_dsn: string;
}
