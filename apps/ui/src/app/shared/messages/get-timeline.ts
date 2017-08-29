export class GetTimeline {
    public schema: string = 'http://jm.forma-pro.com/schemas/message/GetTimeline.json';

    constructor(public jobTemplateId?: string, public limit?: number) {}
}
