import {Policy} from "./policy";

export class SubJobPolicy extends Policy {
    schema: string = 'http://comrade.forma-pro.com/schemas/policy/SubJobPolicy.json';
    parentId: string;
}
