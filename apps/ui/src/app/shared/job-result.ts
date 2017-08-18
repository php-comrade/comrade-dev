import {JobTemplate} from "./job-template";

export class JobResult extends JobTemplate {
    schema: string = "http://jm.forma-pro.com/schemas/JobResult.json";
    status: 1 | 2 | 514 | 18 | 258 | 4 | 12 | 36 | 68 | 132;
    createdAt: Date;
}
