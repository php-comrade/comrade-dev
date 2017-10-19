import {Date} from "../date";

export class GetJobChart {
    public schema: string = 'http://comrade.forma-pro.com/schemas/message/GetJobChart.json';
    public statuses: string[];

    constructor(public templateId: string, public since: Date, public until: Date) {}
}
