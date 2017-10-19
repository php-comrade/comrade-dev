export class JobStatus {
  schema: string = "http://comrade.forma-pro.com/schemas/JobStatus.json";

  public static NEW = 'new';

  public static RUNNING = 'running';

  public static RUNNING_SUB_JOBS = 'running_sub_jobs';

  public static RETRYING = 'retrying';

  public static CANCELED = 'canceled';

  public static COMPLETED = 'completed';

  public static FAILED = 'failed';

  public static TERMINATED = 'terminated';
}
