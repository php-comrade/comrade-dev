import {Job} from "../job";

export class GetDependentJobsResult {
    public schema: string = 'http://comrade.forma-pro.com/schemas/message/GetDependentJobsResult.json';

    jobs: Job[];
}