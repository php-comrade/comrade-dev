import {JobTemplate} from "./job-template";
import {JobResult} from "./job-result";

export class Job extends JobTemplate {
    schema: string = "http://comrade.forma-pro.com/schemas/Job.json";
    id: string;
    results: JobResult[];
    currentResult: JobResult;
}
