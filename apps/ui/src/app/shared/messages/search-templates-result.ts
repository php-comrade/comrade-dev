import {JobTemplate} from "../job-template";

export class SearchTemplatesResult {
    public schema: string = 'http://comrade.forma-pro.com/schemas/message/SearchTemplatesResult.json';

    templates: JobTemplate[];
}