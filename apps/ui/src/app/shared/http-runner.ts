import {Runner} from "./runner";

export class HttpRunner extends Runner {
    schema: string = 'http://comrade.forma-pro.com/schemas/runner/HttpRunner.json';
    url: string;
}
