import {Trigger} from "./trigger";
import {Runner} from "./runner";
import {Date} from "./date";
import {ExclusivePolicy} from "./exclusive-policy";
import {GracePeriodPolicy} from "./grace-period-policy";
import {RetryFailedPolicy} from "./retry-failed-policy";
import {RunSubJobsPolicy} from "./run-sub-jobs-policy";
import {SubJobPolicy} from "./sub-job-policy";
import {RunDependentJobPolicy} from "./run-dependent-job-policy";

export class JobTemplate {
    schema: string = "http://comrade.forma-pro.com/schemas/JobTemplate.json";
    name: string;
    templateId: string;
    createdAt: Date;
    updatedAt: Date;
    payload: any;
    runner: Runner;
    exclusivePolicy: ExclusivePolicy;
    gracePeriodPolicy: GracePeriodPolicy;
    retryFailedPolicy: RetryFailedPolicy;
    runSubJobsPolicy: RunSubJobsPolicy;
    subJobPolicy: SubJobPolicy;
    runDependentJobPolicies: RunDependentJobPolicy[] = [];

    /** @deprecated */
    triggers: Trigger[] = [];

    addRunDependentJobPolicy(policy: RunDependentJobPolicy): void
    {
        this.runDependentJobPolicies = [...this.runDependentJobPolicies, policy];
    }

    removeRunDependentJobPolicy(policy: RunDependentJobPolicy): void
    {
        this.runDependentJobPolicies = this.runDependentJobPolicies.filter(currentPolicy => currentPolicy !== policy);
    }
}
