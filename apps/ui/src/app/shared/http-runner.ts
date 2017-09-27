import {Runner} from "./runner";

export class HttpRunner extends Runner {
    schema: string = 'http://jm.forma-pro.com/schemas/runner/HttpRunner.json';
    url: string;
    sync: boolean;
}
