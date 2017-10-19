import {Policy} from "./policy";

export class ExclusivePolicy extends Policy {
    schema: string = 'http://comrade.forma-pro.com/schemas/policy/ExclusivePolicy.json';
    onDuplicateRun: 'mark_job_as_canceled' | 'mark_job_as_failed';
}
