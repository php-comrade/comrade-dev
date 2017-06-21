import {JobTemplate} from "../job-template";

export class CreateJob {
    public schema: string = 'http://jm.forma-pro.com/schemas/message/CreateJob.json';

    constructor(public jobTemplate: JobTemplate) {}
}