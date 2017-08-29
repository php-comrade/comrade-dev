export class GetJob {
    public schema: string = 'http://jm.forma-pro.com/schemas/message/GetJob.json';

    constructor(public jobId: string) {}
}
