export class JobResultMetrics {
  schema: string = "http://jm.forma-pro.com/schemas/JobResultMetrics.json";
  startTime: number;
  stopTime: number;
  duration: number;
  memory: number;
  logs: string[];
}
