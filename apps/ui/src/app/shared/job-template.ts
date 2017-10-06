import {Trigger} from "./trigger";
import {Runner} from "./runner";
import {Date} from "./date";
import {ExclusivePolicy} from "./exclusive-policy";
import {GracePeriodPolicy} from "./grace-period-policy";
import {RetryFailedPolicy} from "./retry-failed-policy";
import {RunSubJobsPolicy} from "./run-sub-jobs-policy";
import {SubJobPolicy} from "./sub-job-policy";

export class JobTemplate {
    schema: string = "http://jm.forma-pro.com/schemas/JobTemplate.json";
    name: string;
    templateId: string;
    createdAt: Date;
    details: any;
    runner: Runner;
    exclusivePolicy: ExclusivePolicy;
    gracePeriodPolicy: GracePeriodPolicy;
    retryFailedPolicy: RetryFailedPolicy;
    runSubJobsPolicy: RunSubJobsPolicy;
    subJobPolicy: SubJobPolicy;

  /** @deprecated */
  triggers: Trigger[] = [];
}
