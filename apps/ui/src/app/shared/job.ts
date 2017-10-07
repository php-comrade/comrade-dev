import {JobTemplate} from "./job-template";
import {JobResult} from "./job-result";

export class Job extends JobTemplate {
    schema: string = "http://jm.forma-pro.com/schemas/Job.json";
    id: string;
    results: JobResult[];
    currentResult: JobResult;
}
