import {JobTemplate} from "./job-template";

export class JobResult extends JobTemplate {
    schema: string = "http://jm.forma-pro.com/schemas/JobResult.json";
    status: number;
    createdAt: Date;
}
