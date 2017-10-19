import {Date} from './date';
import {JobResultError} from "./job-result-error";
import {JobResultMetrics} from "./job-result-metrics";

export class JobResult {
    schema: string = "http://comrade.forma-pro.com/schemas/JobResult.json";
    status: string;
    createdAt: Date;
    error: JobResultError;
    metrics: JobResultMetrics
}
