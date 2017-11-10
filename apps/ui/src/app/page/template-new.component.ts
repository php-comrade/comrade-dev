import {Component, OnInit} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import {Router} from "@angular/router";
import 'rxjs/add/operator/catch';
import {Trigger} from "../shared/trigger";
import {CronTrigger} from "../shared/cron-trigger";
import {SimpleTrigger} from "../shared/simple-trigger";
import {Runner} from "../shared/runner";
import {ExclusivePolicy} from "../shared/exclusive-policy";
import {GracePeriodPolicy} from "../shared/grace-period-policy";
import {RetryFailedPolicy} from "../shared/retry-failed-policy";
import {RunSubJobsPolicy} from "../shared/run-sub-jobs-policy";
import {Title} from "@angular/platform-browser";
import {SubJobPolicy} from "../shared/sub-job-policy";
import {CreateJob} from "../shared/messages/create-job";
import {RunDependentJobPolicy} from "../shared/run-dependent-job-policy";

@Component({
  selector: 'template-new',
  templateUrl: './template-new.component.html',
})
export class TemplateNewComponent implements OnInit {
  jobTemplate: JobTemplate;
  submitted: boolean;
  message: string;
  addCronTrigger: boolean = false;
  addSimpleTrigger: boolean = false;
  addQueueRunner: boolean = false;
  addHttpRunner: boolean = false;
  addExclusivePolicy: boolean = false;
  addGracePeriodPolicy: boolean = false;
  addRetryFailedPolicy: boolean = false;
  addRunSubJobsPolicy: boolean = false;
  addSubJobPolicy: boolean = false;
  addRunDependentJobPolicy: boolean = false;

  createJob: CreateJob;

  constructor(private jobTemplateService: JobTemplateService, private router: Router, private titleService: Title) {
    const uuidv4 = require('uuid/v4');

    this.jobTemplate = new JobTemplate();
    this.jobTemplate.templateId = uuidv4();
    console.log(this.jobTemplate.templateId);
    this.submitted = false;
    this.message = '';


    this.createJob = new CreateJob(this.jobTemplate);
  }

  ngOnInit():void {
      this.titleService.setTitle('New job - Comrade');
  }

  onFormChange(): void {
     this.message = '';
  }

  onSubmit() {
    this.submitted = true;

    this.jobTemplateService.create(this.createJob)
        .catch(res => { throw res })
        .subscribe(
            res => this.router.navigate(['/template', this.jobTemplate.templateId, 'view', 'summary']),
            err => this.message = err
        );

    this.submitted = false;
  }

  triggerCronTrigger(): void {
      this.addCronTrigger = !this.addCronTrigger;
  }

  triggerSimpleTrigger(): void {
      this.addSimpleTrigger = !this.addSimpleTrigger;
  }

  triggerQueueRunner(): void {
      this.addQueueRunner = !this.addQueueRunner;
  }

  triggerHttpRunner(): void {
    this.addHttpRunner = !this.addHttpRunner;
  }

  triggerExclusivePolicy(): void {
      this.addExclusivePolicy = !this.addExclusivePolicy;
  }

  triggerGracePeriodPolicy(): void {
      this.addGracePeriodPolicy = !this.addGracePeriodPolicy;
  }

  triggerRetryFailedPolicy(): void {
      this.addRetryFailedPolicy = !this.addRetryFailedPolicy;
  }

  triggerRunSubJobsPolicy(): void {
      this.addRunSubJobsPolicy = !this.addRunSubJobsPolicy;
  }

  triggerSubJobPolicy(): void {
    this.addSubJobPolicy = !this.addSubJobPolicy;
  }

  triggerRunDependentJobPolicy(): void {
    this.addRunDependentJobPolicy = !this.addRunDependentJobPolicy;
  }

  onTriggerAdded(trigger: Trigger) {
    trigger.templateId = this.jobTemplate.templateId;

    this.createJob.addTrigger(trigger);

    if (trigger instanceof CronTrigger) {
      this.addCronTrigger = false;
    }
    if (trigger instanceof SimpleTrigger) {
        this.addSimpleTrigger = false;
    }
  }

  onExclusivePolicyAdded(policy: ExclusivePolicy) {
    this.jobTemplate.exclusivePolicy = policy;

    this.addExclusivePolicy = false;
  }

  onGracePeriodPolicyAdded(policy: GracePeriodPolicy) {
      this.jobTemplate.gracePeriodPolicy = policy;

      this.addGracePeriodPolicy = false;
  }

  onRetryFailedPolicyAdded(policy: RetryFailedPolicy) {
      this.jobTemplate.retryFailedPolicy = policy;

      this.addRetryFailedPolicy = false;
  }

  onRunSubJobsPolicyAdded(policy: RunSubJobsPolicy) {
      this.jobTemplate.runSubJobsPolicy = policy;

      this.addRunSubJobsPolicy = false;
  }

  onSubJobPolicyAdded(policy: SubJobPolicy) {
    this.jobTemplate.subJobPolicy = policy;

    this.addSubJobPolicy = false;
  }

  onRunDependentJobPolicyAdded(policy: RunDependentJobPolicy) {
    this.jobTemplate.addRunDependentJobPolicy(policy);

    this.addRunDependentJobPolicy = false;
  }

  onRunnerAdded(runner: Runner) {
    this.jobTemplate.runner = runner;

    this.addQueueRunner = false;
    this.addHttpRunner = false;
  }

  onRemoveTrigger(trigger: Trigger)
  {
    this.createJob.removeTrigger(trigger);
  }

  onRemoveExclusivePolicy() {
    this.jobTemplate.exclusivePolicy = null;
  }

  onRemoveGracePeriodPolicy() {
      this.jobTemplate.gracePeriodPolicy = null;
  }

  onRemoveRetryFailedPolicy() {
      this.jobTemplate.retryFailedPolicy = null;
  }

  onRemoveRunSubJobsPolicy() {
      this.jobTemplate.runSubJobsPolicy = null;
  }

  onRemoveSubJobPolicy() {
    this.jobTemplate.subJobPolicy = null;
  }

  onRemoveRunDependentJobPolicy(policy: RunDependentJobPolicy) {
    this.jobTemplate.removeRunDependentJobPolicy(policy);
  }

  onRemoveRunner() {
    this.jobTemplate.runner = null;
  }
}
