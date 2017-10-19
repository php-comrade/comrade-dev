export class GetJob {
    public schema: string = 'http://comrade.forma-pro.com/schemas/message/GetJob.json';

    constructor(public jobId: string) {}
}
