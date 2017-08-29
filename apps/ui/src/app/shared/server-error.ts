import {Date} from "../shared/date";

export class ServerError {
    error?: {
        raw: string;
        message: string;
        code: number;
        file: string;
        line: string;
        trace: string;
    };

    request?: {
        type: 1 | 2;
        method: string;
        requestUri: string;
        serverProtocol: string;
        attributes: any[];
        raw: string
    };

    cli?: {
        argv: string[];
        command: string;
    };

    queue?: {
        message: {
            properties: any[],
            headers: any[],
            body: string,
            isRedelivered: boolean,
            topicName: string,
            processorName: string,
            processorQueueName: string,
            commandName: string,
        },
        result: { status: string, reason: string }
    };

    id: string;

    createdAt: number;

    createdAtAsDate: Date;
}
