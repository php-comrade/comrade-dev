import {Date} from './date';
import {JobResultError} from "./job-result-error";
import {JobResultMetrics} from "./job-result-metrics";

export class JobResult {
    schema: string = "http://jm.forma-pro.com/schemas/JobResult.json";
    status: 1 | 2 | 514 | 18 | 258 | 4 | 12 | 36 | 68 | 132;
    createdAt: Date;
    error: JobResultError;
    metrics: JobResultMetrics
}
