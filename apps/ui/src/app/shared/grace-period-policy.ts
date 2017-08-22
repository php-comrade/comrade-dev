import {Policy} from "./policy";

export class GracePeriodPolicy extends Policy {
    schema: string = 'http://jm.forma-pro.com/schemas/policy/GracePeriodPolicy.json';
    period: number;
}
