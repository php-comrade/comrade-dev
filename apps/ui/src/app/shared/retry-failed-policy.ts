import {Policy} from "./policy";

export class RetryFailedPolicy extends Policy {
    schema: string = 'http://jm.forma-pro.com/schemas/policy/RetryFailedPolicy.json';
    retryLimit: number;

}