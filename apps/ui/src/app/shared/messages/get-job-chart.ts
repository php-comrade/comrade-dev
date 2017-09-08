import {Date} from "../date";

export class GetJobChart {
    public schema: string = 'http://jm.forma-pro.com/schemas/message/GetJobChart.json';
    public statuses: number[];

    constructor(public templateId: string, public since: Date, public until: Date) {}
}
