import {Date} from './date';
import {JobResultError} from "./job-result-error";
import {JobResultMetrics} from "./job-result-metrics";

export class JobResult {
    schema: string = "http://jm.forma-pro.com/schemas/JobResult.json";
    status: string;
    createdAt: Date;
    error: JobResultError;
    metrics: JobResultMetrics
}
