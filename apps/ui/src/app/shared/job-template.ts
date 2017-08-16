export class JobTemplate {
    schema: string = "http://jm.forma-pro.com/schemas/JobTemplate.json";
    name: string;
    templateId: string;
    processTemplateId: string;
    createdAt: Date;
    details: any;
}
