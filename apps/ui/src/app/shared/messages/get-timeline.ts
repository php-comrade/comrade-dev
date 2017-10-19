export class GetTimeline {
    public schema: string = 'http://comrade.forma-pro.com/schemas/message/GetTimeline.json';

    constructor(public jobTemplateId?: string, public limit?: number) {}
}
