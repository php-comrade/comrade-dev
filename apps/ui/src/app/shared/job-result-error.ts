export class JobResultError {
    schema: string = 'http://comrade.forma-pro.com/schemas/throwable.json;';
    raw: string;
    message: string;
    code: number;
    file: string;
    line: number;
    trace: string;
    previous: JobResultError;
}
