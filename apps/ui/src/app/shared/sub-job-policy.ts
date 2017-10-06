import {Policy} from "./policy";

export class SubJobPolicy extends Policy {
    schema: string = 'http://jm.forma-pro.com/schemas/policy/SubJobPolicy.json';
    parentId: string;
}
