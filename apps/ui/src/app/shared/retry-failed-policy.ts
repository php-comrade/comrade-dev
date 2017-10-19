import {Policy} from "./policy";

export class RetryFailedPolicy extends Policy {
    schema: string = 'http://comrade.forma-pro.com/schemas/policy/RetryFailedPolicy.json';
    retryLimit: number;

}