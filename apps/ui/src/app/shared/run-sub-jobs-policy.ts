import {Policy} from "./policy";

export class RunSubJobsPolicy extends Policy {
    schema: string = 'http://jm.forma-pro.com/schemas/policy/RunSubJobsPolicy.json';
    onFailedSubJob: 'mark_job_as_failed' | 'mark_job_as_completed';
}
